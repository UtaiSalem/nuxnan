# 25 — การเลือกตั้งสภานักเรียน (Student Council Election)

> ไฟล์รองของเมนู **#25 การเลือกตั้ง** — เมนูใหม่ ไม่อยู่ในสารบัญ 24 เมนูเดิมของ [OVERVIEW.md](OVERVIEW.md)
> สังกัด: **ฝ่ายบริหารงานกิจการนักเรียน → งานกิจกรรมนักเรียน** (`SchoolDepartmentSetupService::TEMPLATE` L36-46 ระบุ "สภานักเรียน" ไว้ในคำอธิบายของงานนี้อยู่แล้ว)
> วันที่สแกน + เขียนสเปก: 2026-07-31

---

## 0. ข้อตกลงที่ล็อกแล้ว (ผู้ใช้ตัดสิน 2026-07-31)

| # | ประเด็น | ข้อสรุป |
|---|---|---|
| **E1** | จุดลงคะแนน | **หน่วยเลือกตั้งที่มีกรรมการคุม** — เครื่องของโรงเรียน ล็อกอินด้วยบัญชีครู/กรรมการ เป็นผู้ออกบัตรเลือกตั้งทีละใบ · **ไม่มีการลงคะแนนจากมือถือส่วนตัว** |
| **E2** | ยืนยันตัวตนผู้มาใช้สิทธิ์ | สแกน QR บนบัตรนักเรียน **หรือ** พิมพ์รหัส (`member_code` / `student_number`) — ผ่าน `StudentIdentifierResolver` ตัวเดิม · **ไม่ต้องมีตัวยืนยันที่สอง** เพราะมีกรรมการเป็นด่านตรวจตัวจริง |
| **E3** | ความลับของบัตร | **บัตรลับ** — ระบบต้องรู้ว่า *ใครมาใช้สิทธิ์แล้ว* แต่ต้องไม่มีทางรู้ว่า *ใครเลือกเบอร์ไหน* → แยกตาราง `election_voter_receipts` (มีตัวตน ไม่มีตัวเลือก) กับ `election_ballots` (มีตัวเลือก ไม่มีตัวตน ไม่มีเวลา) |
| **E4** | รูปแบบบัตร | **1 คน เลือก 1 เบอร์** — ผู้สมัครเป็น **พรรค/ทีม มีเบอร์** หัวหน้าพรรค = ผู้สมัครประธานนักเรียน · มีช่อง **"ไม่ประสงค์ลงคะแนน"** |
| **E5** | ผู้มีสิทธิ์ | **นักเรียนทุกคน + ครู/บุคลากร** — ล็อกบัญชีรายชื่อเป็น snapshot ตอนเปิดการเลือกตั้ง |
| **E7** | **แยกประถม/มัธยม** (เพิ่ม 2026-08-01) | โรงเรียนไทย**จัดเลือกตั้งแยกระดับเสมอ** → `elections.education_level` (`null` = ทั้งโรงเรียน · `1` = ประถม · `2` = มัธยม) · นักเรียนคัดจาก `student_academic_info.education_level` · **บุคลากรคัดจาก `academy_members.education_level` ที่ต้องระบุรายคน** |
| **E6** | ประกาศผล | **หลังปิดหีบเท่านั้น** — ระหว่างเปิดหีบเห็นได้แค่จำนวนผู้มาใช้สิทธิ์/เปอร์เซ็นต์ ไม่เห็นคะแนนรายเบอร์แม้แต่แอดมิน |

---

## 1. Reality Check — ของเดิมในระบบ (สแกน 2026-07-31)

### ไม่มีอะไรให้ต่อยอดเลยในโดเมนนี้

ไม่มี `Election` / `Ballot` / `Candidate` / `Voter` / `Nomination` — ไม่มี model, table, controller, route, page ใน `api/nuxnanravel/**` และ `ui/**` · ไม่มีเอกสารแผนใน `.agents/` (มีแค่ 1 บรรทัดใน `school-5-departments-revised-analysis.md:142` ที่เสนอแนวทางไว้ลอย ๆ)

### ข้อเสนอเดิมในเอกสารใช้ไม่ได้ — บันทึกไว้กันเสนอซ้ำ

`school-5-departments-revised-analysis.md:142` เขียนว่า *"ใช้ `AcademyGroup` (type=student_council) + `SchoolEvent` (type=election) ได้"*

**ตรวจแล้วใช้ไม่ได้ด้วยเหตุผลเชิงโครงสร้าง ไม่ใช่แค่ยังไม่มี key:**
- `AcademyGroupTypes.php:11-68` ไม่มี `student_council` (มี `committee` ใกล้เคียงที่สุด) · `SchoolEvent::TYPES` L62-105 ไม่มี `election`
- แต่ต่อให้เพิ่ม key ทั้งสอง **`SchoolEvent` ก็เก็บบัตรลับไม่ได้** เพราะเส้นทางบันทึกการเข้าร่วมทั้งหมด (`event_registrations` unique `[event_id, user_id]`, `activity_attendances` unique `[session_id, user_id]`) ผูกตัวตนกับแถวที่บันทึกโดยตรง ซึ่งขัดกับ E3 ทั้งข้อ
- `AcademyGroup type=student_council` ใช้ได้จริง แต่เป็นคนละเรื่อง — มันคือ **ผลลัพธ์** ของการเลือกตั้ง (คณะกรรมการสภานักเรียนที่ได้รับเลือก) ไม่ใช่กระบวนการเลือกตั้ง → เก็บไว้เป็นงานปลายทาง (E-S12)

### สิ่งที่ใช้ต่อได้จริง

| ของเดิม | ใช้ทำอะไร | สถานะ |
|---|---|---|
| `app/Services/StudentIdentifierResolver.php` (77 บรรทัด) | resolve `member_code`/`student_number` → `user_id` ที่หน่วยเลือกตั้ง · **Strategy 1 รับ member_code ตัวเลข ซึ่งครูก็มี** → ใช้กับผู้มีสิทธิ์ทุกคนได้โดยไม่ต้องแก้ | ใช้ได้ตามเดิม |
| `ui/composables/useQRScanner.ts` (554) + `ui/components/qr/UniversalQRModal.vue` (652) + `ui/types/qr.ts` | กล้องสแกน QR + registry ที่มี type `student_card` อยู่แล้ว | ใช้ได้ ต่อ handler ใหม่ |
| `StudentCard::qr_content` (`StudentCard.php:38-54`) | `STUDENT:{academy_id}:{student_number}` — QR ที่พิมพ์บนบัตรจริง | ใช้ได้ (ดู §2 ข้อจำกัด) |
| `app/Services/Campaign/CampaignViewService.php::rewardedView()` L34-58 | **แม่แบบการเขียนครั้งเดียวแบบทนการชนกัน** — เปิด transaction → ล็อกแถวแม่ → เช็ค key ในล็อก → เขียน | ลอกรูปมาใช้กับการลงคะแนน |
| `app/Models/AcademyPermission.php` L20-24, L51-182 | ระบบสิทธิ์ + allow-list ที่มอบให้ฝ่ายได้ | เพิ่มตระกูล `elections` |
| `app/Http/Middleware/CheckAcademyPermission` + `AcademyGroupPermissionAccessService` | สิทธิ์ระดับฝ่าย (เพิ่งใช้งานได้จริง 2026-07-29 ดู [09-departments.md](09-departments.md) D-S3/D-S4) | ใช้ได้ตามเดิม |
| `app/Models/AcademyActivityLog` (เมนู #2–#5, #9 ใช้อยู่) | audit log | ขยาย action ใหม่ |

### ⛔ ห้ามใช้ — บันทึกไว้กันหยิบผิด

- **`polls` / `poll_votes`** (feed poll) — `poll_votes` มี `unique(poll_id, user_id)` **พร้อม `poll_option_id` ในแถวเดียวกัน** = บัตรไม่ลับโดยสิ้นเชิง · ไม่มีขอบเขต academy (มีแค่ `user_id` + `is_public`) · สร้างโพลหักแต้มผู้ใช้ 180 pp (`PollController.php:54`) · **และมีบั๊กค้าง**: `question_options` ไม่มีคอลัมน์ `votes` ในทุก migration แต่ `PollVoteController.php:72` สั่ง `increment('votes')` และ `PollResource.php:38,50` อ่านค่านั้น
- **`PollVoteController.php:34-43`** — เช็คโหวตซ้ำ **นอก** transaction (ซึ่งเริ่มที่ L46) แล้วปล่อยให้ unique index รับหน้าที่ พอชนกันจริง `QueryException` ถูกกลืนที่ L84-89 เป็น HTTP 500 · **ห้ามลอกลำดับนี้**
- **`teams` / `team_user`** — ซาก Jetstream ไม่มี Model
- **`Campaign*`** — เป็นเรื่องโฆษณา/บริจาค ไม่ใช่การหาเสียง (ลอกได้แค่ `review_status` workflow)

---

## 2. ข้อจำกัดด้านความปลอดภัยที่ต้องยอมรับ (เขียนไว้ให้เห็นชัด)

### 2.1 QR บนบัตรนักเรียนไม่ใช่ความลับ

`StudentCard::qr_content` = `STUDENT:{academy_id}:{student_number}` — คำนวณจากรหัสนักเรียนล้วน ๆ ไม่มีลายเซ็น ไม่มีค่าสุ่ม → **ใครก็ประกอบ QR ของทั้งโรงเรียนได้จากรหัสที่เรียงกัน**

→ **E1 (หน่วยเลือกตั้งมีกรรมการคุม) คือมาตรการชดเชยข้อนี้ทั้งหมด** ไม่ใช่แค่ความสะดวก · ถ้าวันหนึ่งจะเปิดให้ลงคะแนนจากมือถือส่วนตัว **ต้องเพิ่มตัวยืนยันที่สองก่อนเสมอ** ห้ามเปิดโดยใช้ของที่มีอยู่

### 2.2 ความลับของบัตรมี "จุดรั่วที่เหลืออยู่" 1 จุด — ต้องรู้และยอมรับ

การออกใบเสร็จ (`receipt.status = cast`) กับการหย่อนบัตร (`INSERT election_ballots`) อยู่ใน **transaction เดียวกัน** → ใน MySQL binlog สองเหตุการณ์นี้อยู่ติดกัน **คนที่เข้าถึง binlog ของเซิร์ฟเวอร์ได้ จับคู่ผู้ลงคะแนนกับบัตรได้**

- มาตรการที่ทำในสเปกนี้: ตาราง `election_ballots` **ไม่มี `created_at`/`updated_at` และใช้ UUIDv4 เป็น PK** (ไม่ใช่ auto-increment / ไม่ใช่ ULID ที่เรียงตามเวลา) → ลำดับการหย่อนบัตรอ่านจากตัวตารางไม่ได้ · `receipts.cast_at` เก็บความละเอียดระดับ**นาที**เท่านั้น
- ที่เหลือคือความเสี่ยงระดับ **ผู้ดูแลเซิร์ฟเวอร์** ซึ่งแก้ด้วยโค้ดแอปไม่ได้ → ถ้าจำเป็นต้องปิดจริง ทางเลือกคือหย่อนบัตรลง staging แล้วสับเปลี่ยนตอนปิดหีบ (**ไม่ทำในเฟสนี้** — ซับซ้อนเกินความเสี่ยงจริงของโรงเรียน)
- **ต้องแจ้งกรรมการการเลือกตั้งให้ทราบข้อนี้** ก่อนใช้งานจริง อย่าโฆษณาว่า "ลับ 100%"

### 2.3 การนับคะแนนต้องพิสูจน์ได้

ค่าคงที่ที่ระบบต้องตรวจทุกครั้งก่อนประกาศผล:

```
COUNT(election_ballots WHERE election_id = X)
  == COUNT(election_voter_receipts WHERE election_id = X AND status = 'cast')
```

ถ้าไม่เท่ากัน **ห้ามประกาศผล** ต้องขึ้น error พร้อมตัวเลขทั้งสองฝั่ง

---

## 3. Schema (7 ตารางใหม่)

> ห้าม `migrate:fresh/refresh/reset` — DB เครื่องนี้มีข้อมูลจริง (นักเรียน 2,931 คน)

### 3.1 `elections`

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| `id` | bigint PK | |
| `academy_id` | FK → academies, cascade | |
| `academic_year_id` | FK → academic_years, nullable | |
| `title` | string(150) | เช่น "การเลือกตั้งประธานนักเรียน ปีการศึกษา 2569" |
| `description` | text nullable | |
| `status` | enum | `draft, nomination, campaign, voting, closed, published, cancelled` |
| `nomination_opens_at` / `nomination_closes_at` | datetime nullable | ช่วงรับสมัคร |
| `voting_opens_at` / `voting_closes_at` | datetime nullable | ช่วงลงคะแนน |
| `allow_abstain` | boolean default **true** | ช่อง "ไม่ประสงค์ลงคะแนน" |
| `ballot_ttl_seconds` | unsigned int default **180** | อายุบัตรที่ออกให้แต่ละคน |
| `voter_roll_locked_at` | datetime nullable | เวลาที่ปิดบัญชีผู้มีสิทธิ์ |
| `published_at` | datetime nullable | |
| `created_by` | FK → users | |
| `settings` | json nullable | เผื่ออนาคต |
| timestamps + softDeletes | | |

Index: `[academy_id, status]`

**Status machine (บังคับใน service ห้ามให้ update ตรง):**
```
draft → nomination → campaign → voting → closed → published
  ↘ cancelled (จากสถานะใดก็ได้ ยกเว้น published)
```
กฎที่ต้องบังคับ:
- เข้า `voting` ไม่ได้ถ้า `voter_roll_locked_at` เป็น null หรือ พรรคที่ `approved` < 1
- เข้า `closed` แล้ว **ย้อนกลับไป `voting` ไม่ได้เด็ดขาด**
- `published` แก้ไข/ลบ election ไม่ได้ทุกกรณี

### 3.2 `election_parties` (พรรค/ทีมผู้สมัคร)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| `id` | bigint PK | |
| `election_id` | FK cascade | |
| `number` | unsigned smallint nullable | **เบอร์** — ให้ตอนอนุมัติ ไม่ใช่ตอนสมัคร |
| `name` | string(120) | ชื่อพรรค |
| `slogan` | string(200) nullable | |
| `logo_path` | string nullable | โลโก้พรรค |
| `policy` | text nullable | นโยบาย |
| `status` | enum | `pending, approved, rejected, withdrawn` |
| `applied_by` | FK → users | |
| `reviewed_by` | FK → users nullable | |
| `reviewed_at` | datetime nullable | |
| `review_note` | text nullable | เหตุผลที่ไม่อนุมัติ |
| timestamps + softDeletes | | |

Unique: `[election_id, number]` (partial ไม่ได้ใน MySQL → `number` nullable + ตรวจซ้ำใน service ก่อน assign) · Unique: `[election_id, name]`

### 3.3 `election_party_members` (ทีมบริหารของพรรค)

`id, party_id (FK cascade), user_id (FK), role enum(leader, deputy, secretary, treasurer, member), position_label string(80) nullable, sort_order, timestamps`

- Unique `[party_id, user_id]`
- **บังคับใน service:** 1 พรรคต้องมี `role = leader` พอดี 1 คน (= ผู้สมัครประธาน) · 1 คนอยู่ได้พรรคเดียวต่อ 1 election (ตรวจข้ามพรรคก่อนอนุมัติ)

### 3.4 `election_voters` (บัญชีผู้มีสิทธิ์ — snapshot)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| `id` | bigint PK | |
| `election_id` | FK cascade | |
| `user_id` | FK → users | |
| `academy_member_id` | FK → academy_members nullable | |
| `member_code` | string(20) nullable | คัดลอกมาตอน lock |
| `display_name` | string(150) | คัดลอกมา — ชื่อเปลี่ยนทีหลังไม่กระทบบัญชี |
| `voter_type` | enum | `student, staff` |
| `grade_level` | string(10) nullable | ม.1–ม.6 (เฉพาะ student) |
| `classroom_name` | string(50) nullable | ม.1/1 |
| `student_number` | string(20) nullable | สำหรับสแกนบัตร |
| timestamps | | |

Unique `[election_id, user_id]` · Index `[election_id, grade_level]`, `[election_id, member_code]`, `[election_id, student_number]`

**ที่มาของข้อมูลตอน lock:** `academy_members` ที่ `status = 2` (อนุมัติแล้ว) ของ academy นั้น → มี `student_id` = `student` (join `classroom_students` status=`active` ในปีการศึกษาปัจจุบัน เพื่อได้ `grade_level`/`classroom_name`) · ไม่มี `student_id` = `staff`

> ### 🔄 ตัวเลขจริงหลังชำระข้อมูลทะเบียน 2026-07-31 — **บัญชีผู้มีสิทธิ์ = 2,340 คน** (นักเรียน 2,212 + บุคลากร 128)
>
> เดิม 3,058 คน · ลดลง 2 ครั้งจากการชำระข้อมูล ไม่ใช่จากการแก้ตรรกะการนับ:
>
> | รอบ | ทำอะไร | บัญชีเหลือ |
> |---|---|---|
> | เริ่มต้น | — | 3,058 |
> | **จำหน่ายออก 449 คน** | ฝ่ายทะเบียนยืนยันว่าพ้นสภาพแล้ว → `students.status = transferred` + `academy_members.status = 5` (`STATUS_DISCHARGED`) · กลุ่มนี้ถูกนำเข้า 2025-10-12 โดยไม่มีข้อมูลชั้น/ห้อง และ**ไม่เคยลงทะเบียนห้องเรียนปีใดเลย** · สำรองไว้ที่ `backup-449-before-discharge.csv` | 2,609 |
> | **กรองศิษย์เก่าออก 269 คน** | จบ ม.6 แล้ว 267 + ม.4 2 · **ยังเป็นสมาชิกและล็อกอินได้ตามนโยบายโรงเรียน แต่ไม่มีสิทธิ์เลือกตั้ง** → `lock()` join `students.status` เพิ่ม + รายงาน `skipped_inactive_student` | **2,340** |
>
> **บทเรียนสำคัญ:** `lock()` เดิมตัดสินสิทธิ์จาก `academy_members.status` อย่างเดียว **ไม่ได้ดู `students.status` เลย** → แก้สถานะนักเรียนอย่างเดียวไม่มีผลกับบัญชีผู้มีสิทธิ์ ต้องแก้ทั้งสองที่หรือแก้ตัวกรอง
>
> **ผลพลอยได้:** คนที่สแกนบัตรไม่ได้ลดจาก 735 → **17 คน** · คนที่ไม่มีชั้น/ห้องเหลือ 137 = บุคลากร 128 + นักเรียน 9 คนที่ถูกถอนจากห้อง ม.4 (รอฝ่ายทะเบียนตัดสินรายคน)
>
> **ตัวเลขเดิมก่อนชำระข้อมูล (เก็บไว้อ้างอิง):** 3,063 คน = student 2,931 + staff 132 (สมาชิก 3,064 แถว มี status=3 อยู่ 1 แถวที่ต้องคัดออก)
> ⚠️ **4 คนไม่มี `member_code`** และ **นักเรียน 284 คนไม่มีบัตรนักเรียน** (student_cards 2,647 < students 2,931) → คนกลุ่มนี้สแกนไม่ได้ ต้องให้กรรมการค้นด้วยชื่อแล้วยืนยันด้วยตา → **หน้าหน่วยเลือกตั้งต้องมีช่องค้นหาด้วยชื่อ ไม่ใช่แค่สแกน/พิมพ์รหัส**

### 3.5 `election_voter_receipts` (ใบเสร็จผู้มาใช้สิทธิ์ — มีตัวตน ไม่มีตัวเลือก)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| `id` | bigint PK | |
| `election_id` | FK cascade | |
| `election_voter_id` | FK → election_voters | |
| `user_id` | FK → users | ซ้ำกับ voter เพื่อ unique index ที่อ่านง่าย |
| `station_id` | FK → election_stations | |
| `issued_by` | FK → users | กรรมการที่ออกบัตรให้ |
| `status` | enum | `issued, cast, void, expired` |
| `token_hash` | char(64) nullable | sha256 ของ ballot token · **ล้างเป็น null ทันทีที่ cast** |
| `token_expires_at` | datetime nullable | |
| `issued_at` | datetime | |
| `cast_at` | datetime nullable | **เก็บความละเอียดระดับนาที** (วินาที = 00) |
| `void_reason` | string(200) nullable | |
| timestamps | | |

**Unique `[election_id, user_id]`** ← หัวใจของ "1 คน 1 สิทธิ์" · Index `[election_id, status]`, `[station_id]`

> การออกบัตรใหม่ให้คนเดิม (กรณีจอค้าง/ปิดเบราว์เซอร์) = **update แถวเดิม** จาก `void`/`expired` กลับเป็น `issued` พร้อม token ใหม่ **ไม่ใช่ insert แถวใหม่** — unique index จะกันไว้อยู่แล้ว และทุกครั้งต้องลง audit log

### 3.6 `election_ballots` (บัตรที่หย่อนแล้ว — มีตัวเลือก ไม่มีตัวตน ไม่มีเวลา)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| `uuid` | **char(36) PK (UUIDv4)** | **ห้ามใช้ auto-increment และห้ามใช้ ULID** — ทั้งสองอย่างเรียงตามเวลาที่หย่อน |
| `election_id` | FK cascade, indexed | |
| `party_id` | FK → election_parties **nullable** | `null` = ไม่ประสงค์ลงคะแนน |

**`$timestamps = false` · ไม่มี `created_at` · ไม่มี `updated_at` · ไม่มีคอลัมน์ใดที่ชี้กลับไปหาผู้ลงคะแนน**

Model ต้องกำหนด `$incrementing = false`, `$keyType = 'string'`, `public $timestamps = false`

### 3.7 `election_stations` (หน่วยเลือกตั้ง)

`id, election_id (FK cascade), name string(100), location string(150) nullable, is_open boolean default false, opened_by FK users nullable, opened_at datetime nullable, closed_by FK users nullable, closed_at datetime nullable, timestamps`

Unique `[election_id, name]`

### 3.8 `election_results` (ผลที่ประกาศแล้ว — แช่แข็ง)

`id, election_id (FK cascade), party_id (FK nullable = ไม่ประสงค์ลงคะแนน), votes unsigned int, rank unsigned smallint nullable, is_winner boolean default false, published_at datetime **nullable**, published_by FK users **nullable**, timestamps`

> ⚠️ **แก้สเปก 2026-07-31 (ความผิดพลาดของ claude เอง):** ฉบับแรกเขียน `published_at` / `published_by` แบบไม่ระบุ nullable → E-S1 จึงสร้างเป็น NOT NULL · **แต่ E-S7 แยกเป็นสองจังหวะ: `closeAndCount` นับแล้วเขียนแถวโดยยัง*ไม่*ประกาศ (`published_at` เป็น null) แล้ว `publish` ค่อยเติมทีหลัง** สองอย่างนี้อยู่ด้วยกันไม่ได้ → ต้องมี migration แก้เป็น nullable (ตารางยังว่าง 0 แถว จึงปลอดภัย)

Unique `[election_id, party_id]`

> ⚠️ **ข้อจำกัดที่ยืนยันด้วยการทดลองจริงบน DB นี้ (2026-07-31):** MySQL ไม่ถือว่า `NULL` ซ้ำกัน → unique index นี้ **กันแถว "ไม่ประสงค์ลงคะแนน" (`party_id = NULL`) ซ้ำไม่ได้** · ทดลองใส่ 2 แถวติดกันแล้วผ่านทั้งคู่ (ทดลองใน transaction ที่ rollback แล้ว)
> → **E-S7 ต้องปิดช่องนี้** เลือกทางใดทางหนึ่ง: (ก) upsert ด้วย `whereNull('party_id')` ในเซอร์วิส **และ** ห้าม publish ซ้ำถ้า `published_at` มีค่าแล้ว หรือ (ข) เพิ่ม generated column `party_key AS IFNULL(party_id,0) STORED` แล้ว unique `[election_id, party_key]` — **แนะนำ (ข)** เพราะบังคับที่ระดับ DB ไม่ต้องเชื่อว่าทุกเส้นทางเรียกเซอร์วิสตัวเดียวกัน

> นับจาก `election_ballots` **ครั้งเดียว** ตอนประกาศผล แล้วแช่ไว้ · ทุกหน้าจอที่แสดงผลอ่านจากตารางนี้ **ห้ามนับสดจาก ballots** เพื่อให้ผลที่ประกาศไปแล้วเปลี่ยนไม่ได้แม้ข้อมูลข้างหลังถูกแตะ

---

## 4. Permission

เพิ่มตระกูล `elections` ใน `AcademyPermission::PERMISSIONS`:

| key | display_name | ใคร |
|---|---|---|
| `elections.view` | ดูการเลือกตั้งและผลคะแนน | ทุกคนในโรงเรียน |
| `elections.manage` | จัดการการเลือกตั้ง: สร้าง/รับสมัคร/อนุมัติพรรค/เปิด-ปิดหีบ/ประกาศผล | กกต. (งานกิจกรรมนักเรียน) |
| `elections.station` | ประจำหน่วยเลือกตั้ง: เปิดหน่วยและออกบัตรเลือกตั้ง | กรรมการประจำหน่วย (ครูที่ได้รับมอบหมาย) |

**เพิ่ม `'elections'` ใน `AcademyPermission::DEPARTMENT_DELEGABLE_FAMILIES` (L20-24)** → ฝ่ายบริหารงานกิจการนักเรียนรับสิทธิ์นี้ได้ตามโมเดล #9

Permission Matrix:

| Permission | Owner | Admin | ฝ่ายกิจการนักเรียน | ครูประจำหน่วย | ครูทั่วไป | นักเรียน |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| `elections.view` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `elections.manage` | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `elections.station` | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |

> **กฎเหล็ก:** ทุก route ต้องมี `academy.permission:<key>` ตั้งแต่ commit แรก — **ห้ามเปิดชั่วคราวแบบเมนู #6** เพราะนี่คือการเลือกตั้ง ไม่ใช่หน้าดูข้อมูล (เทียบบทเรียน D1 ใน [09-departments.md](09-departments.md) ที่ route 15 ตัวไม่มี guard)

---

## 5. API (routes/learn/election.php — ไฟล์ใหม่ ผูกเข้า academy.php)

Prefix: `/api/academies/{academy}/elections`

### จัดการ (`elections.manage`)
| Method | Path | หน้าที่ |
|---|---|---|
| GET | `/` | รายการการเลือกตั้ง |
| POST | `/` | สร้าง (สถานะ `draft`) |
| GET | `/{election}` | รายละเอียด |
| PUT | `/{election}` | แก้ไข (บล็อกเมื่อ `published`) |
| POST | `/{election}/status` | เปลี่ยนสถานะผ่าน state machine |
| POST | `/{election}/voter-roll/lock` | สร้าง snapshot บัญชีผู้มีสิทธิ์ (idempotent, ทำซ้ำได้จนกว่าจะเข้า `voting`) |
| GET | `/{election}/voter-roll` | รายชื่อ + สถิติแยกระดับชั้น (paginated) |
| GET | `/{election}/parties` | รายการพรรคทุกสถานะ |
| POST | `/{election}/parties/{party}/approve` | อนุมัติ + **assign เบอร์** |
| POST | `/{election}/parties/{party}/reject` | ไม่อนุมัติ + เหตุผล |
| POST | `/{election}/stations` · PUT/DELETE `/stations/{station}` | จัดการหน่วย |
| POST | `/{election}/close-and-count` | ปิดหีบ + นับ + ตรวจ invariant §2.3 (ยังไม่ประกาศ) |
| POST | `/{election}/publish` | ประกาศผล |
| GET | `/{election}/audit-log` | บันทึกการดำเนินการ |

### รับสมัคร (`elections.view` + เป็นผู้มีสิทธิ์)
| Method | Path | หน้าที่ |
|---|---|---|
| POST | `/{election}/parties` | สมัครพรรค (เฉพาะช่วง `nomination`) |
| PUT | `/{election}/parties/{party}` | แก้ใบสมัครตัวเอง (เฉพาะ `pending`) |
| POST | `/{election}/parties/{party}/withdraw` | ถอนตัว |

### หน่วยเลือกตั้ง (`elections.station`)
| Method | Path | หน้าที่ |
|---|---|---|
| POST | `/{election}/stations/{station}/open` · `/close` | เปิด/ปิดหน่วย |
| POST | `/{election}/stations/{station}/lookup` | **สแกน/พิมพ์รหัส/ค้นชื่อ → คืนตัวตน + สถานะสิทธิ์** (ยังไม่ออกบัตร) |
| POST | `/{election}/stations/{station}/issue` | **ออกบัตรเลือกตั้ง** → คืน `ballot_token` + รายการพรรค |
| POST | `/{election}/stations/{station}/void` | ยกเลิกบัตรที่ออกไปแล้ว (จอค้าง/นักเรียนเปลี่ยนใจไม่ลง) |
| GET | `/{election}/stations/{station}/progress` | จำนวนผู้มาใช้สิทธิ์ของหน่วยนี้ |

### ลงคะแนน (ไม่ต้องมี permission — ใช้ `ballot_token` เป็นหลักฐานแทน)
| Method | Path | หน้าที่ |
|---|---|---|
| POST | `/{election}/cast` | body: `ballot_token`, `party_id` (nullable = ไม่ประสงค์ลงคะแนน) |

> route นี้ยังต้องอยู่หลัง `auth:api` (เครื่องกรรมการล็อกอินอยู่) + **throttle** · token ตรวจด้วย `hash_equals` เทียบ `token_hash` **ห้าม query ด้วย token ตรง ๆ**

### ผลคะแนน (`elections.view`)
| Method | Path | หน้าที่ |
|---|---|---|
| GET | `/{election}/turnout` | ผู้มาใช้สิทธิ์/เปอร์เซ็นต์ แยกระดับชั้น/หน่วย — **ใช้ได้ระหว่างเปิดหีบ** |
| GET | `/{election}/results` | ผลคะแนน — **404/403 จนกว่าจะ `published`** (บังคับใน controller ไม่ใช่แค่ซ่อนใน UI) |

---

## 6. เส้นทางการลงคะแนน (ลำดับที่ต้อง implement ให้ตรง)

```
[1] กรรมการเปิดหน่วย (station.open)  — เครื่องโรงเรียน ล็อกอินครู
[2] นักเรียน/ครูเดินมา → สแกน QR บัตร | พิมพ์รหัส | ค้นชื่อ
        → POST /lookup → StudentIdentifierResolver → user_id
        → ตรวจ: อยู่ใน election_voters ไหม / มี receipt status=cast แล้วหรือยัง
        → จอกรรมการแสดง ชื่อ + รูป + ชั้น/ห้อง ให้ยืนยันด้วยตา
[3] กรรมการกด "ออกบัตร" → POST /issue
        → transaction: ล็อกแถว election_voters
          → upsert receipt (unique election_id+user_id กัน 2 ใบ)
          → status=issued, token_hash=sha256(random_bytes(32)), token_expires_at=+180s
        → คืน ballot_token (ค่าดิบ) + รายการพรรคที่ approved เรียงตามเบอร์
[4] ส่งจอให้ผู้ลงคะแนน (หรือหันจอ) → เลือก 1 เบอร์ หรือ "ไม่ประสงค์ลงคะแนน" → ยืนยัน
[5] POST /cast { ballot_token, party_id }
        → transaction:
            SELECT ... FOR UPDATE บน receipt ที่ token_hash ตรง
            ตรวจ status=issued && token_expires_at > now && election.status=voting && station.is_open
            INSERT election_ballots (uuid4, election_id, party_id)   ← ไม่มีตัวตน ไม่มีเวลา
            UPDATE receipt SET status='cast', cast_at=<นาทีปัจจุบัน>, token_hash=NULL
        → commit → จอขึ้น "ลงคะแนนเรียบร้อย" แล้วเด้งกลับหน้าสแกนภายใน 3 วินาที
[6] หมดเวลา/ยกเลิก → receipt → status='void' + void_reason → ออกใหม่ได้
```

**จุดที่พลาดง่ายและต้องมีเทสต์คุม:**
- ยิง `/cast` ด้วย token เดิม 2 ครั้งพร้อมกัน → ต้องได้บัตร **1 ใบ** และครั้งที่สองได้ 409 พร้อมข้อความไทย ไม่ใช่ 500
- ยิง `/issue` ให้คนเดิม 2 เครื่องพร้อมกัน → ต้องมี receipt **1 แถว** และ token ที่ออกก่อนถูกยกเลิก
- ปิดหีบระหว่างที่มีบัตรค้างอยู่ในมือ → `/cast` ต้องถูกปฏิเสธ และ receipt ค้างต้องถูกกวาดเป็น `expired`
- `party_id` ที่ส่งมาต้องเป็นพรรคของ election นั้น และ `status = approved` เท่านั้น

---

## 7. Frontend

| หน้า | path | สิทธิ์ | หมายเหตุ |
|---|---|---|---|
| รายการ/จัดการการเลือกตั้ง | `ui/pages/academies/[name]/admin/elections/index.vue` | `elections.manage` | + เมนูในกลุ่ม "กิจกรรม & การสื่อสาร" ของ `admin.vue:195-210` |
| รายละเอียด + แท็บ | `.../admin/elections/[id].vue` | `elections.manage` | แท็บ: ภาพรวม · พรรค · บัญชีผู้มีสิทธิ์ · หน่วย · ผล · บันทึก |
| **หน้าหน่วยเลือกตั้ง** | `.../elections/[id]/station.vue` | `elections.station` | **หน้าสำคัญที่สุด** — โหมดเต็มจอ 2 สถานะ: สแกน ↔ บัตรเลือกตั้ง |
| สมัครพรรค | `.../elections/[id]/apply.vue` | ผู้มีสิทธิ์ | |
| ผลคะแนน | `.../elections/[id]/results.vue` | `elections.view` | ขึ้น "ยังไม่ประกาศผล" จนกว่าจะ published |

**ข้อกำหนดของหน้าหน่วยเลือกตั้ง (station.vue):**
- 3 ทางเข้า: กล้องสแกน QR · ช่องพิมพ์รหัส · **ช่องค้นหาด้วยชื่อ** (จำเป็น เพราะ 284 คนไม่มีบัตร + 4 คนไม่มี member_code)
- แสดง **รูป + ชื่อ + ชั้น/ห้อง** ให้กรรมการยืนยันด้วยตาก่อนกดออกบัตร
- ตอนแสดงบัตรเลือกตั้ง: **ซ่อนชื่อผู้ลงคะแนนออกจากจอทั้งหมด** (กันคนข้างหลังเห็นว่าใครกำลังเลือกอะไร) + ปุ่มใหญ่พอสำหรับสัมผัส + นับถอยหลังอายุบัตร
- หลังยืนยัน: จอแสดงผลสำเร็จ **โดยไม่ทวนว่าเลือกเบอร์อะไร** แล้วเด้งกลับหน้าสแกนอัตโนมัติ
- ต้องทำงานได้บนแท็บเล็ต/มือถือแนวตั้ง (กรรมการถือเดิน)
- ใช้สกิล `hopeui-port` เป็นต้นแบบ markup ตาม convention โปรเจค

**QR:** เพิ่มการรองรับ `STUDENT:` ใน flow ของหน้านี้โดยเฉพาะ — `ui/types/qr.ts` มี type `student_card` อยู่แล้ว ให้ station.vue ดักผลลัพธ์เอง **ห้ามไปแก้ `parseQRCode()` ให้ route ไปหน้าอื่นอัตโนมัติ** เพราะจะกระทบ UniversalQRModal ที่ใช้ร่วมกันทั้งระบบ

---

---

## 7.1 สัญญาข้อมูลจริงของหน้าหน่วยเลือกตั้ง (ยืนยันกับ DB จริง 2026-08-23)

ตรวจโดยสร้าง election + หน่วย + พรรค + ผู้มีสิทธิ์ 1 คนใน `DB::beginTransaction()` แล้วเรียก `ElectionStationService` ตรง ๆ แล้ว `rollBack()` (ตาราง `elections` กลับเป็น 0 แถวตามเดิม)

### สิ่งที่ backend ส่งจริง

| endpoint | คีย์ที่ส่งจริง |
|---|---|
| `POST .../stations/{s}/lookup` | `user_id` · `name` · `photo` · `classroom` · `status` · `status_label` |
| `GET .../stations/{s}/search` | paginator ของแถว `election_voters` → `id` `election_id` `user_id` `academy_member_id` `member_code` `display_name` `voter_type` `grade_level` `classroom_name` `student_number` |
| `POST .../stations/{s}/issue` | `ballot_token` · `parties` · `allow_abstain` |
| `GET .../stations/{s}/progress` | `issued` · `cast` · `remaining` |

### สิ่งที่ `station.vue` อ่าน (ของจริงจากการรัน)

| ตัวแปรในหน้า | ค่าที่ได้จริง | ผล |
|---|---|---|
| `voter.status_code` | **UNDEFINED** (backend ส่ง `status`) | 🔴 **ปุ่ม "ออกบัตรเลือกตั้ง" ไม่แสดงเลย** และ `issue()` `return` ออกก่อนยิง API — ทั้ง 3 ทางเข้าตายหมด |
| `voter.photo_url` | **UNDEFINED** (backend ส่ง `photo`) | 🔴 กรรมการไม่เห็นรูปก่อนออกบัตร ซึ่ง §7 กำหนดเป็นข้อบังคับ |
| `voter.classroom_name` | **UNDEFINED** (backend ส่ง `classroom`) | 🔴 ห้องเรียนไม่ขึ้นจากทางสแกน/กรอกรหัส |
| `voter.grade_level` | **UNDEFINED** (`lookup` ไม่ส่ง ทั้งที่ตาราง `election_voters` มีคอลัมน์นี้) | 🔴 ระดับชั้นไม่ขึ้น |
| `voter.display_name` | UNDEFINED แต่ fallback `voter.name` ทำงาน | 🟢 ชื่อขึ้นได้ |
| `station.issued_count` / `station.cast_count` | **UNDEFINED** (backend ส่ง `issued`/`cast`) | 🔴 ตัวนับค้าง 0 · 0 ตลอดงาน |
| `station.name` / `station.is_open` | **UNDEFINED** (`progress` ไม่ส่งสองค่านี้) | 🔴 ป้ายขึ้น "ปิดหน่วย" ตลอดแม้หน่วยเปิดอยู่ → **กรรมการอ่านจอแล้วเข้าใจผิดทั้งวัน** |
| `data.ballot_ttl_seconds` | **UNDEFINED** (`issue` ไม่ส่ง) | 🔴 นับถอยหลังตรึงที่ 180 · ถ้าโรงเรียนตั้ง `ballot_ttl_seconds` เป็นค่าอื่น เลขบนจอกับอายุบัตรจริงจะไม่ตรงกัน |

### รูปผู้มาใช้สิทธิ์ — แก้ชื่อฟิลด์อย่างเดียวยังไม่พอ

`StudentIdentifierResolver` คืน `student_photo` จาก `users.profile_photo_path` **ทั้งสอง strategy** ไม่เคยแตะ `student_cards.profile_image` เลย · ตัวเลขจริงของโรงเรียนนี้:

| แหล่งรูป | มีรูป |
|---|---|
| `users.profile_photo_path` (ที่ lookup ใช้อยู่) | **344 / 2,613 คน (13%)** |
| `student_cards.profile_image` | **1,696 / 2,647 ใบ (64%)** |

→ ต่อให้แก้ `photo_url` → `photo` แล้ว กรรมการก็ยังไม่เห็นรูป 87% ของคนที่มา · **E-S8b ต้องให้ `lookup()` เลือก `student_cards.profile_image` ก่อน แล้วค่อย fallback ไป `users.profile_photo_path`** และแก้ที่ `ElectionStationService::lookup()` **ไม่ใช่ที่ `StudentIdentifierResolver`** เพราะตัวหลังใช้ร่วมกับระบบเช็คชื่อ (เมนู #18/#26) การเปลี่ยนรูปคืนค่าจะลามข้ามเมนู

### ข้อบกพร่องอื่นในหน้าเดียวกัน

| # | อาการ | ผล |
|---|---|---|
| 1 | `open()` ประกาศไว้แต่**ไม่มีปุ่มไหนเรียก** | กรรมการเปิดหน่วยจากหน้านี้ไม่ได้ · `issue()` ฝั่ง backend โยน "สถานียังไม่ได้เปิดลงคะแนน" จนกว่าจะมีคนไปเปิดผ่าน API เอง · ไม่มีปุ่มปิดหน่วยด้วย |
| 2 | `stationId = route.query.station หรือ 1` | หน่วย id 1 อาจเป็นของการเลือกตั้งอื่น → controller `abort 404` โดยหน้าไม่แสดงอะไรบอกเลย · ต้องมีตัวเลือกหน่วยจากรายการจริง (ผูกกับแท็บ "หน่วย" ของ E-S9 ที่จะแจกลิงก์พร้อม `?station=`) |
| 3 | หลัง `cast` สำเร็จ ล้างแค่ `mode`/`voter`/`selected` | `code`, `query`, `searchResults`, `ballotParties`, `token` ค้างไว้ → **คนถัดไปเดินมาเห็นรหัส/ชื่อของคนก่อนหน้าค้างอยู่บนจอ** |
| 4 | ปุ่มสแกนเรียก `startScanning(video, canvas)` ทั้งที่ฟังก์ชันไม่รับพารามิเตอร์ | ไม่พัง แต่เป็นหลักฐานว่าหน้านี้ไม่เคยถูกกดจริงสักครั้ง |
| 5 | รายการผลค้นหาอยู่**นอก `<main>` เหนือ header** | บนมือถือรายชื่อโผล่เหนือหัวเรื่อง ไม่ได้อยู่ในการ์ดยืนยันตัวตน |
| 6 | ทั้งไฟล์เป็น 47 บรรทัดแต่บางบรรทัดยาว 2,000+ ตัวอักษร ไม่ผ่าน `hopeui-port` | ผิด convention โปรเจค · แก้/รีวิวทีหลังแทบไม่ได้ |
| 7 | `const toast = useToast()` ประกาศแล้วไม่ใช้ | โค้ดตาย |

**สิ่งที่หน้านี้ทำถูกแล้วและห้ามแก้ให้เสีย:** โหมด `ballot` ไม่แสดงชื่อผู้ลงคะแนนบนจอ · หน้า `done` ไม่ทวนว่าเลือกเบอร์อะไร · `onUnmounted` ปิดกล้อง + เคลียร์ timer · abstain ส่ง `party_id: null` ตรงกับที่ controller คาด


---

## 7.2 route-model binding ของโดเมนเลือกตั้งพัง — พิสูจน์แล้วด้วย HTTP จริง (2026-08-23) · ✅ แก้แล้วใน E-S8c

> **สถานะ:** ปิดแล้วโดย `a015b208` · เก็บบันทึกไว้เพราะกับดักนี้ไม่มีเทสต์ระดับ service ตัวไหนจับได้ และคอนโทรลเลอร์อื่นในเรพอาจมีรูปแบบเดียวกัน

**ทั้ง §7.1 ของ claude และเทสต์ทั้ง 121 ตัวของ codex ตรวจที่ชั้น service ทั้งหมด ไม่มีใครยิงผ่าน HTTP สักครั้ง** — พอยิงจริงถึงเห็นว่าชั้น routing พังมาตั้งแต่ E-S3/E-S5

### ต้นเหตุ

`ImplicitRouteBinding::resolveForRoute()` ของ Laravel จับคู่ route parameter กับอาร์กิวเมนต์ **ด้วยชื่อ** (`getParameterName()` เทียบชื่อตรงหรือ snake_case) แต่สองคอนโทรลเลอร์นี้ตั้งชื่อย่อ:

| คอนโทรลเลอร์ | ลายเซ็น | route parameter | ตรงกันไหม |
|---|---|---|---|
| `ElectionStationController` (9 เมธอด รวม `cast`) | `$a, $e, $s` | `{academy} {election} {station}` | ❌ |
| `ElectionPartyController` (6 เมธอด) | `$a, $e, $p` | `{academy} {election} {party}` | ❌ |
| `ElectionController` (ทุกเมธอด) | `$academy, $election` | เหมือนกัน | ✅ |
| `ElectionVoterRollController` | เลี่ยงด้วย `Academy::findOrFail($r->route('academy'))` เอง | — | ✅ (เลี่ยงไว้แล้ว) |

พอจับคู่ไม่ได้ Laravel ไม่ throw — มันข้ามไป แล้ว `RouteDependencyResolverTrait` สร้าง **อินสแตนซ์เปล่า** จาก container ยัดให้แทน
ผลคือ `abort_if($election->academy_id !== $academy->id, 404)` กลายเป็น `null !== null` = ผ่านด่านไปเฉย ๆ แล้วไปพังหรือคืนค่าว่างที่ชั้นล่าง

### ผลการยิงจริง (feature test ผ่าน `actingAs(...,'api')` บน route จริง)

| endpoint | ผล |
|---|---|
| `POST .../stations/{s}/open` | **404** `No query results for model [Election]` · `is_open` ใน DB ยัง `false` |
| `POST .../stations/{s}/lookup` | **404** |
| `POST .../stations/{s}/issue` | **404** |
| `GET .../stations/{s}/progress` | **404** |
| `GET .../stations/{s}/search` | **200 แต่ paginator ว่าง** (ค้นใน election ที่ไม่มีตัวตน) — ผิดแบบเงียบ อันตรายกว่า 404 |
| **`POST .../elections/{e}/cast`** | **422 "ไม่พบบัตรลงคะแนนที่ใช้งานได้" · `election_ballots` 0 แถว** → **นักเรียนลงคะแนนไม่ได้แม้ถือ token ที่ถูกต้อง** |
| `GET .../elections/{e}/parties` | **200 แต่ `data: []`** ทั้งที่มีพรรคอยู่จริง |
| `GET .../elections/{e}` (ตัวควบคุม, ชื่ออาร์กิวเมนต์ถูก) | **200 พร้อมข้อมูลจริง** → ยืนยันว่าปัญหาอยู่ที่ชื่ออาร์กิวเมนต์ ไม่ใช่ที่ auth/middleware/สภาพแวดล้อม |

### ทำไมเทสต์ 121 ตัวจับไม่ได้

- `ElectionStationTest` เรียก `app(ElectionStationService::class)->...` ตรง ๆ ทุกเคส
- เทสต์ `progress` ที่ codex เพิ่งเพิ่ม เรียก `app(ElectionStationController::class)->progress($a, $e, (string) $station->id)` — **เรียกเมธอดตรง ๆ ข้าม routing ทั้งชั้น**
- เทสต์ HTTP ตัวเดียวที่มี (`test_station_from_another_election_is_not_found`) คาดหวัง **404 อยู่แล้ว** → มันผ่านด้วยเหตุผลที่ผิด

→ **กฎเพิ่มจากรอบนี้: ทุก endpoint ที่หน้าจอเรียกจริง ต้องมีเทสต์ที่ยิงผ่าน route จริงอย่างน้อยหนึ่งเคสที่คาดหวัง 200** เทสต์ระดับ service พิสูจน์แค่ตรรกะ ไม่ได้พิสูจน์ว่าเรียกถึง


## 8. Implementation Tasks

| Step | Title | Depends | Deliverable | Status |
|---|---|---|---|---|
| **E-S1** | **Schema + Models + Permission** — 8 migration, 8 model (`ElectionBallot` ต้อง `$incrementing=false` + `$keyType='string'` + `$timestamps=false`), ตระกูล `elections` 3 key + เพิ่มใน `DEPARTMENT_DELEGABLE_FAMILIES` | — | migrations + models + tests | 🟢 **verified 2026-07-31** |
| **E-S2** | **Election CRUD + State Machine + Audit** — `ElectionService::transitionTo()` บังคับกฎ §3.1 · route ทุกตัวมี guard ตั้งแต่แรก | E-S1 | controller + service + routes + tests | 🟢 **verified 2026-07-31** |
| **E-S3** | **รับสมัครพรรค + อนุมัติ + ให้เบอร์** — บังคับ 1 leader/พรรค, 1 คน 1 พรรค, เบอร์ไม่ซ้ำ | E-S2 | controller + service + tests | 🟢 **verified 2026-07-31** |
| **E-S4** | **Lock บัญชีผู้มีสิทธิ์** — snapshot จาก `academy_members` status=2 + join ห้องเรียน · idempotent · รายงานตัวเลขแยก student/staff และ **แยกรายชื่อคนที่ไม่มี member_code / ไม่มีบัตร ออกมาให้เห็น** | E-S2 | endpoint + service + tests | 🟢 **verified 2026-07-31** |
| **E-S5** | **หน่วยเลือกตั้ง + ออกบัตร** — station CRUD/open/close · `/lookup` (ต่อ `StudentIdentifierResolver` + ค้นด้วยชื่อ) · `/issue` (ล็อกแถว + token hash + TTL) · `/void` | E-S4 | controller + service + tests รวมเคสยิงพร้อมกัน | 🟢 **verified 2026-07-31** |
| **E-S6** | **ลงคะแนน (บัตรลับ)** — `/cast` ตามรูป `CampaignViewService::rewardedView()` · **ต้องมีเทสต์ยืนยันว่าไม่มีคอลัมน์ใดใน `election_ballots` ชี้กลับหาผู้ลงคะแนน** | E-S5 | controller + service + tests | 🟢 **verified 2026-07-31** |
| **E-S7** | **ปิดหีบ + นับ + ประกาศผล** — ตรวจ invariant §2.3 ก่อนเสมอ · แช่ผลใน `election_results` · `/results` ปฏิเสธก่อน published · จัดอันดับ **พร้อมจัดการคะแนนเท่ากัน** (เมนูอื่นในระบบยังไม่มีตัวไหนทำ) | E-S6 | service + tests | 🟢 **verified 2026-07-31** |
| **E-S8** | **หน้าหน่วยเลือกตั้ง (station.vue)** — งาน frontend ที่สำคัญที่สุด ตาม §7 | E-S6 | FE | 🟢 **ปิดแล้ว 2026-08-23 หลังผ่าน E-S8b + E-S8c** · รอบแรก (`4e21d176`) ไม่ผ่าน ดู §7.1 · เหลือค้างเชิงรูปแบบ: template ยังเป็นบรรทัดยาว และ**ยังไม่เคยเปิดบนเบราว์เซอร์จริง** (อยู่ในการซ้อม §9) |
| **E-S8b** | **อุดผลตรวจ E-S8** — จัดสัญญาข้อมูลให้ตรงตาม §7.1 · เพิ่มปุ่มเปิด/ปิดหน่วย · แหล่งรูปจากบัตรนักเรียน · ล้างสถานะหลังลงคะแนน | E-S8 | FE + BE (`lookup`/`progress`) | 🟢 **verified 2026-08-23** (`194bbbb5` + `73d21076`) |
| **E-S8c** | **แก้ route-model binding ของโดเมนเลือกตั้ง (§7.2)** — ตั้งชื่ออาร์กิวเมนต์ให้ตรง route parameter ทั้ง `ElectionStationController` (9 เมธอด รวม `cast`) และ `ElectionPartyController` (6 เมธอด) · เทสต์ระดับ HTTP · แยก `user_id` ออกจาก `member_code` ตอนเลือกจากผลค้นหา | E-S8b | BE + FE | 🟢 **verified 2026-08-23** (`a015b208` + `b71a9475`) — ยิง HTTP จริงแล้วได้ 200 ทุกเส้น · `cast` เพิ่มบัตรจริง 1 ใบ |
| **E-S9** | **หน้าแอดมิน** — index + [id] 6 แท็บ + เมนูใน `admin.vue` | E-S7 | FE | 🟢 **ปิดแล้ว 2026-08-23** ผ่าน E-S9a → E-S9d2 |
| **E-S10** | **หน้าสมัครพรรค + หน้าผลคะแนน + turnout realtime** — สเปกเต็มใน §12 · แตกเป็น E-S10a (BE) / E-S10b (apply) / E-S10c (results+index) | E-S9 | BE + FE | 🟢 **ปิดแล้ว 2026-08-24** · P1 แก้ด้วย migration backfill สิทธิ์+role · P2 ตัดสินว่าจัดเฉพาะมัธยม (ดู §12.6) |
| **E-S11** | **Hardening** — **สเปกเต็มใน §13** · throttle มีอยู่แล้ว 4 เส้นแต่คีย์ผิดหน่วย (§13.1) · 429 เป็นภาษาอังกฤษบนจอหน่วย · กวาด receipt ค้างเป็น `expired` · pint + ชุดเทสต์เต็ม · แตกเป็น E-S11a–d | E-S10 | BE + FE | ⚪ |
| **E-S12** | 🟢 **ปิดแล้ว 2026-08-24** · **ต่อยอด: ตั้งคณะกรรมการสภานักเรียนจากผลเลือกตั้ง** — **สเปกเต็มใน §14** · type ใหม่ `student_council` จากพรรคที่ชนะ · **ต้องเพิ่ม type ทั้ง `AcademyGroupTypes.php` และ `ui/constants/academyGroupTypes.ts` (สองไฟล์นี้เป็น mirror กัน)** · แตกเป็น E-S12a (BE) / E-S12b (FE) | E-S7 | BE + FE | ⚪ |

**เส้นทางที่สั้นที่สุดที่จัดเลือกตั้งได้จริง:** E-S1 → E-S2 → E-S3 → E-S4 → E-S5 → E-S6 → E-S7 → E-S8 (หน้าแอดมินยังทำมือผ่าน API ได้ชั่วคราว แต่ **หน้าหน่วยเลือกตั้งข้ามไม่ได้**)

**Rule:** ทุก step ต้อง verify (test/build/ตัวเลขจาก DB จริง) ก่อนขึ้น 🟢 · commit เป็นชุดเล็กต่อ step · ห้าม `migrate:fresh`

### 🔴 กฎที่ได้จาก E-S4: เทสต์เขียวไม่พอ ต้องรันกับข้อมูลจริง

E-S4 เจอบั๊ก **3 รอบติด และทั้ง 3 รอบเทสต์ผ่านหมด**:

| รอบ | บั๊ก | ทำไมเทสต์ไม่จับ |
|---|---|---|
| 1 | `lock()` **พังทั้งกระบวนการ** — `display_name cannot be null` เพราะสมาชิก 2 คนมี `user_id = null` | factory สร้างผู้ใช้ครบเสมอ ไม่มีเคส user หาย |
| 2 | รายงาน `total 3061` แต่เขียนจริง 3058 | ไม่มีเคส "คนเดียวมีสมาชิก 2 แถว" |
| 3 | **memory หมด + ยิง query ~2,900 ครั้ง** (`get()` ทั้งตาราง + `exists()` ต่อคน) | เทสต์มีสมาชิกไม่กี่คน และไม่มีอะไรวัดจำนวน query |

→ **กฎถาวร: step ไหนที่แตะข้อมูลระดับทั้งโรงเรียน ต้องรัน service ตัวนั้นกับ DB จริงใน `DB::beginTransaction()` … `rollBack()` และดูตัวเลข/จำนวน query/memory ก่อนขึ้น 🟢** เทียบเคียงบั๊ก D-S3 ของเมนู #9 ที่เทสต์ 6 เคสแรกจับไม่ได้เพราะทุกเคสสร้างผู้ใช้ที่มี role อยู่แล้ว — **รูปร่างข้อมูลจริงคือสิ่งที่ factory ไม่เคยสร้าง**

---

## 9. สิ่งที่ต้องซ้อมก่อนวันจริง (ไม่ใช่งานโค้ด)

- [ ] สร้าง election ทดสอบ + พรรคปลอม 3 พรรค แล้ว **ซ้อมทั้งกระบวนการกับนักเรียน 1 ห้อง** ก่อนวันจริง
- [ ] ตรวจว่านักเรียน 284 คนที่ไม่มีบัตร กับ 4 คนที่ไม่มี `member_code` ถูกจัดการยังไง (ทำบัตรเพิ่ม / ใช้ช่องค้นชื่อ)
- [ ] เตรียมเครื่อง + เน็ตสำรองที่หน่วยเลือกตั้ง — **ระบบนี้ไม่มีโหมดออฟไลน์** เน็ตหลุด = ลงคะแนนไม่ได้
- [ ] แจ้ง กกต. นักเรียนเรื่องข้อจำกัดใน §2.2 ตามตรง

---

## 9.1 แยกเลือกตั้งประถม/มัธยม (E7) — ตัวเลขจริง 2026-08-01

> ⚠️ **อัปเดต 2026-08-24: ตัวเลขในหัวข้อนี้ล้าสมัยแล้ว และผู้ใช้ตัดสินว่าจะจัดเฉพาะมัธยม**
> ข้อมูลปัจจุบัน: ทั้งโรงเรียน 2,321 · มัธยม 2,193 · **ประถม 0** (ไม่มีแถว `education_level = 1` เหลือแล้ว)
> ดู §12.6 P2 — ส่วนที่เหลือของหัวข้อนี้เก็บไว้เป็นบันทึกว่าทำอะไรไปเมื่อ 2026-08-01

| การเลือกตั้ง | ผู้มีสิทธิ์ | นักเรียน | บุคลากร |
|---|---|---|---|
| ทั้งโรงเรียน (`null`) | 2,789 | 2,661 | 128 |
| **มัธยม** (`2`) | **2,212** | 2,212 | 0 |
| **ประถม** (`1`) | **449** | 449 | 0 |

**ตีตราระดับให้ชัด ไม่ใช้ "ไม่มีห้องเรียน = ประถม"** — นั่นเป็นความบังเอิญของข้อมูล ถ้าวันหนึ่งมีนักเรียนมัธยมตกหล่นจากการจัดห้อง บัญชีผู้มีสิทธิ์จะผิดโดยไม่มีใครรู้ · เขียน `student_academic_info.education_level = 1` ให้นักเรียนประถม 449 คน (INSERT ล้วน ทั้งกลุ่มไม่เคยมีแถวมาก่อน · id ที่แทรกเก็บไว้ที่ `inserted-primary-ids.txt`)

### 🔴 สองข้อที่ต้องทำก่อนเปิดหีบ

1. **ครู 132 คนยังไม่มีใครถูกระบุระดับ** → `staff_without_level = 131` · ถ้าล็อกบัญชีตอนนี้ **การเลือกตั้งทั้งสองระดับจะไม่มีครูลงคะแนนเลยสักคน** · ระบบเลือกที่จะ**รายงานตัวเลขนี้ออกมา** แทนที่จะเดาแทน เพราะทั้งการตัดครูออกเงียบ ๆ และการใส่เขาเข้าทั้งสองการเลือกตั้งเงียบ ๆ ผิดพอกัน → ตั้งระดับผ่าน `PUT /members/{member}/education-level`
2. **นักเรียนประถม 449 คนไม่มีบัตรนักเรียนเลยสักใบ** → เลือกตั้งประถมสแกน QR ไม่ได้ทั้งกลุ่ม ต้องใช้ช่องพิมพ์ `member_code` (มีครบทุกคน) หรือทำบัตรก่อน

> **"แยกบริหารเสมือนคนละโรงเรียน" ยังไม่ได้ทำ** — ต้องมีผู้ดูแล/สิทธิ์แยกตามระดับ ซึ่งผูกกับโมเดลฝ่ายของเมนู #9 · ตอนนี้ทำได้แค่แยก *การเลือกตั้ง* ไม่ได้แยก *การบริหาร*

---

## 10. Review Log

- **2026-08-24 E-S12a + E-S12b (+ E-S12a2 เก็บตก)** — codex + agy ทำ, claude ตรวจ → **ผ่านทั้งคู่ · เมนู #25 ปิดครบทุก step**
  - **T2 ถูกแก้ตรงจุด** — codex ย้ายลูป seed permission จาก `AcademyGroupController::store()` ไปที่
    `AcademyGroupPermissionService::seedDefaults()` แล้วให้ทั้งสองทางเรียกจุดเดียวกัน (ไม่ copy-paste)
  - 🔴 **รอบแรกหลุดสัญญา §14.4 จนหน้าจอใช้จริงไม่ได้ทั้งสองเส้นทาง**
    (ก) ตอบ `{success, group}` ขณะที่ทั้ง controller นี้ตอบ `data` และหน้าจออ่าน `.data`
    → ลิงก์กลายเป็น `/groups/undefined` · (ข) เคส "ตั้งซ้ำ" ยัด id/ชื่อลงในข้อความไทย
    (`"... (group_id: 5, group_name: X)"`) แทนที่จะเป็นฟิลด์ → **ปุ่มลิงก์ไปกลุ่มเดิมไม่ขึ้นเลย
    ซึ่งเป็นทั้งหมดของข้อตัดสิน G4** · **หน้าจอเขียนถูกตามสัญญา ฝั่งที่หลุดคือ backend** จึงสั่งแก้ฝั่งเดียว
  - 🔴 **รอบแรกไม่มีเทสต์เลยสักตัวอีกครั้ง** (147/449 เป๊ะเท่าเดิม) — **รูปแบบเดียวกับ E-S11b**
    จับได้ด้วยวิธีเดิมคือ **นับจำนวนเทสต์เทียบฐานที่จดไว้** ไม่ใช่การอ่านรายงาน
  - เก็บตกอีก 3 ข้อที่ claude เจอเอง: `academy_group_admins.role` เขียน `'admin'` ทั้งที่ระบบใช้ `'leader'`
    (ตรวจจาก `AcademyGroupAdminController` เอง) · การ์ดอยู่นอก transaction เป็น check-then-act
    (แก้เป็นอยู่ในทรานแซกชัน + `lockForUpdate()`) · `$leader` เป็น null แล้ว fatal
  - **claude เทียบ mirror ทั้ง 10 type ทีละฟิลด์ด้วยสคริปต์** (dump `AcademyGroupTypes::TYPES` เป็น JSON
    แล้ว eval array ฝั่ง TS มาเทียบ) → **0 mismatch** · สคริปต์เก็บไว้ที่ scratchpad ใช้ซ้ำได้ทุกครั้งที่แตะสองไฟล์นี้
  - **เทสต์ 155 ผ่าน (471 assertions) · pint ผ่าน · กวาด SFC 753 ไฟล์พัง 0 · dev server ตอบ 200** — claude รันเอง
  - 🟡 **สิ่งที่ claude ไม่ได้ทำ:** ไม่ได้ยิง endpoint กับฐาน dev จริงเพื่อดู body ด้วยตา เพราะต้องสร้าง
    election + พรรค + ผล + กลุ่ม ค้างไว้ในฐานที่มีนักเรียนจริง 2,931 คน แล้วตามลบ 6 ตาราง —
    ความเสี่ยงสูงกว่าประโยชน์ เพราะเทสต์ทั้ง 8 เคสยิงผ่าน route จริงและ **assert คีย์ตรงตัวที่หน้าจออ่าน**
    (`data.id` · `group_id` เทียบกับ `data.id` ของครั้งแรก · `group_name`) ซึ่งคือการเทียบคีย์ตาม §7.1 อยู่แล้ว
- **2026-08-24 E-S11a + E-S11b (+ E-S11ab2 เก็บตก)** — codex ทำ, claude ตรวจ → **ผ่านทั้งคู่ · เหลือ E-S11c (จอ) กับ E-S11d**
  - **limiter คีย์ต่อหน่วยจริง** — ยืนยันใน vendor ว่า `ThrottleRequests::handleRequestUsingNamedLimiter()` ทำ
    `md5($limiterName.$limit->key)` → `election-issue` กับ `election-lookup` ที่คีย์ด้วย station id เหมือนกัน **คนละถัง**
    · เทสต์ยิงเต็ม 60 ที่หน่วย A ได้ 429 แล้วหน่วย B ยังได้ 422 ด้วยบัญชีเดียวกัน · `route:list -v` ไม่เหลือ `throttle:30,1` แล้ว
  - **429 ตรงสัญญา §13.5** และคง header เดิมทั้งชุด · ตรวจ vendor แล้วว่าคีย์ `Retry-After` ตัวพิมพ์ตรงกัน `retry_after` จึงไม่มีทางเป็น 0
  - **claude รันกับ DB จริงเองใน `beginTransaction()` … `rollBack()`** — ก่อนกวาด: live count = 1 ขณะที่ `status='issued'` ดิบ = 2
    → **ข้อตัดสิน §13.3 ข้อ 1 เป็นจริง จอหน่วยถูกโดยไม่ต้องพึ่ง cron** · หลังกวาด `token_hash` เป็น NULL · ใบเสร็จที่ยังไม่หมดอายุไม่ถูกแตะ
  - 🔴 **รอบแรก E-S11b ไม่ได้เขียนเทสต์เลยสักตัว** — ชุดขึ้นจาก 141 เป็น 142 และ +1 นั้นเป็นของ E-S11a
    **จับได้เพราะนับจำนวนเทสต์เทียบฐาน ไม่ใช่เพราะอ่านรายงาน** · ต้องส่ง E-S11ab2 กลับไปเก็บ 4 เคสที่ขาด
  - 🔴 **รอบแรก `expireStaleAll()` เป็น 1+N query** (วน `Election::query()->each()` ยิง UPDATE ทีละตัว)
    เป็น job ที่รันทุก 5 นาทีและ N โตทุกครั้งที่สร้างการเลือกตั้ง → E-S11ab2 รวบเหลือ **UPDATE เดียว**
    claude วัดเองกับ 3 election จริง: **1 query** (ก่อนหน้าได้ 2 query ต่อ 1 election)
  - **เทสต์ 147 ผ่าน (449 assertions) · pint ผ่าน** — claude รันเอง (ฐานก่อนหน้า 141/312)
  - 🟡 เคสชี้ขาดที่สั่งเป็นพิเศษแล้วได้ผล: ใบเสร็จ `cast` ที่ token หมดอายุแล้ว **ต้องไม่ถูกกวาด** — ถ้าพลาดคือคะแนนหาย
    และ invariant §2.3 พังตอนปิดหีบ · และเคส B1 ต้องไม่เรียก `expireStaleAll()` ก่อนเช็ค ไม่งั้นเทสต์เขียวโดยไม่พิสูจน์อะไร (claude อ่านโค้ดยืนยันเอง)
- **2026-08-24 E-S10d + E-S10d2** — codex ทำ, claude กู้ฐาน + ตรวจ → **ผ่าน · P1 ของ §12.6 ปิดแล้ว นักเรียนเข้าเมนู #25 ได้จริง**
  - migration `2026_08_24_000001_backfill_election_permissions_and_member_roles` ทำสองอย่าง:
    เติม `elections.*` ตามเมทริกซ์ §4 (director/admin ครบสามตัว · teacher view+station · staff/finance_staff/registrar/student view · **parent/guest ไม่ได้** · owner เป็น `*` อยู่แล้ว)
    และผูก role ให้สมาชิก status=2 ที่ `academy_role_id` เป็น NULL (มี `student_id` → `student` · ไม่มี → `staff`)
  - **claude ยิง HTTP จริงยืนยันผล:** นักเรียนจริงยิง `GET /elections` ได้ **200** (ก่อน migration ได้ 403) · ยิง `POST /elections` ยังได้ **403** ตามที่ควร (ได้แค่ `view` ไม่ได้ `manage`)
  - **rollback พิสูจน์แล้วว่ากลับมาตรงเป๊ะ** — claude รัน `migrate` → `rollback` → `migrate` เอง:
    หลัง migrate `NULL=1 · teacher=119 · staff=12 · student=2,481 · backfill 2,447 แถว` ·
    หลัง rollback **กลับเป็น `NULL=2,448 · teacher=119 · student=46 · staff=0` · ไม่มี `elections.*` เหลือ · ตาราง backfill หาย · `owner` ยังเป็น `*`**
    (แถวที่เหลือเป็น NULL 1 แถวคือสมาชิกที่ `user_id` เป็น NULL ซึ่ง migration ข้ามถูกต้อง)
  - **เทสต์ 141 ผ่าน (312 assertions) · pint ผ่าน** — claude รันเอง (ฐานก่อนหน้า 139/301 · เพิ่มเทสต์ round-trip ของ migration)
  - 🔴 **รอบแรก (E-S10d) พังและทำฐาน dev เพี้ยน** — `Schema::create()` ทำให้ MySQL implicit commit แล้ว `catch` ของ migration ไป `dropIfExists` ตารางบันทึกทิ้ง → สมาชิก 2,447 คนถูกผูก role ไปแล้วแต่**ไม่มีประวัติให้ย้อน** · codex รายงานตรงและหยุดถูกจังหวะ ไม่เดาแก้ต่อ
  - 🔴 **บั๊กที่ codex ยังไม่เห็น claude เจอเอง: migration ตัวนั้นทำเทสต์พังทั้งชุด 8/8** เพราะฐานเทสต์ (SQLite + RefreshDatabase) ไม่มีแถว role `student` แล้ว migration โยน `RuntimeException` ตั้งแต่ setup — **ย้ำบทเรียนเดิม: migration ที่รันบน dev ผ่าน ไม่ได้แปลว่ารันบนฐานเปล่าผ่าน** → แก้เป็น "โยนเฉพาะเมื่อมีสมาชิกที่ต้องผูก role จริงแต่หา role ไม่เจอ" ฐานเปล่าจึงจบแบบ no-op
  - **วิธีกู้ฐาน dev (claude ทำเอง):** การ update ของ migration ไม่แตะ `updated_at` → แถวที่ถูกผูก role ยังคง `updated_at` เดิม (ก.พ./มิ.ย.) ส่วนคนที่มี role มาก่อนถูกตั้งค่าเมื่อ ก.ค. · นับได้ **46 คนพอดี = จำนวน role `student` เดิม** และครู 119 คนก็เป็น ก.ค. เหมือนกัน (ยืนยันว่าเดือนนั้นคือตอนไล่ตั้ง role) → คืนค่าเฉพาะแถว `updated_at < 2026-07-01` ได้ **2,447 แถวพอดี** ตรงกับที่ migration แตะ · **ภายหลัง `down()` จริงคืนค่าได้ตรงเป๊ะ เท่ากับยืนยันการระบุตัวนี้ซ้ำอีกชั้น**
  - 🟡 หนี้เล็ก: `up()` ยังมี `try { ... } catch (Throwable $e) { throw $e; }` ที่ไม่ทำอะไร (เหลือจากตอนแก้) ไม่กระทบการทำงาน
- **2026-08-24 E-S10 (a/a2/a3 codex + b/c agy, ขนานกัน)** — claude ตรวจ → **โค้ดผ่าน แต่เมนูนี้ยังใช้จริงไม่ได้เพราะสิทธิ์กับข้อมูล** · รายละเอียดสเปก §12 · ผลตรวจ §12.6
  - **หน้าใหม่ 3 หน้า:** `elections/index.vue` (177 บรรทัด) · `elections/[id]/apply.vue` (517) · `elections/[id]/results.vue` (401) + ลิงก์ "การเลือกตั้ง" ใน quickActions ของ dashboard นักเรียน (+6 บรรทัด ไม่มี deletion)
  - **backend:** `GET /{e}/candidates` · `GET /{e}/parties/mine` · turnout คีย์ใหม่ · ซ่อน draft · กรองผู้สมัครตามระดับ · แยก `eligibleMembersQuery()`
  - **claude ยิง HTTP จริงเองทุกเส้น** (สร้าง election ชั่วคราว 2 ตัวในฐาน dev แล้วลบทิ้งหมด · ฐานกลับสภาพเดิม):
    `GET /elections` เจ้าของเห็น draft / สมาชิก view-only ไม่เห็น · `parties/mine` 200 + `data: null` ตอนยังไม่สมัคร ·
    `candidates?q=<1 ตัว>` 422 ข้อความไทย · `candidates?q=สุ` คืน 20 แถวมีครบ 6 ฟิลด์ ไม่มี PII เกิน ·
    `POST /parties` ด้วยบัญชีนักเรียนจริง 201 แล้ว `parties/mine` เห็นใบสมัครตัวเอง · `turnout` คีย์ใหม่ครบ ไม่มี NaN
  - **A1 พิสูจน์ว่าไม่เปลี่ยนพฤติกรรมด้วยการรันเทียบสองรอบ** — `lock()` กับ DB จริงใน transaction ทั้งโค้ดใหม่และโค้ดเก่า (`git stash` แล้วรันซ้ำ) ได้ **ตัวเลขเท่ากันทั้ง 10 คีย์ + จำนวน query เท่ากัน** (2321 / 2193 / 0 · queries 32/34/15) แล้ว rollback
  - **เทสต์ 139 ผ่าน (301 assertions) · pint ผ่าน** — claude รันเอง (ฐานเดิม 131/254 · เพิ่ม 8 เทสต์ยิงผ่าน route จริงทั้งหมด)
  - 🟢 **เทสต์ method spoofing ผ่าน** — `POST /parties/{party}` พร้อม `_method=PUT` แบบ multipart เข้าเมธอด `update()` จริง → เส้นทางอัปโหลดโลโก้ตอนแก้ใบสมัครของ `apply.vue` ใช้ได้
  - 🔴 **บล็อกเกอร์ 1 — นักเรียนไม่มี `elections.view` เลยสักคน** ยิงจริงได้ **403 `Insufficient permissions`** · role `student` (id 7) มี permission 7 ตัวไม่มี `elections.*` · และ **สมาชิกที่อนุมัติแล้ว 2,448 จาก 2,613 คนไม่มี role ผูกอยู่เลย** (`academy_role_id = NULL` → `hasPermission()` false เสมอ) · §4 เขียนว่า `elections.view` = "ทุกคนในโรงเรียน" แต่ข้อมูลจริงไม่เป็นแบบนั้น → **ทั้ง 3 หน้าใหม่เด้งนักเรียนออกหมด** · การลงคะแนนไม่กระทบเพราะ `/cast` ใช้ `ballot_token` ไม่มี permission guard
  - 🔴 **บล็อกเกอร์ 2 — ข้อมูลประถมหายไปแล้ว** `student_academic_info` ที่ `is_current = 1` มี 2,194 แถว **เป็น `education_level = 2` ทั้งหมด ไม่มีระดับ 1 เหลือเลย** → รัน `lock()` ของการเลือกตั้งประถมได้ **total = 0 · skipped_other_level = 2193** · ตัวเลข 449 คนใน §9.1 ไม่ตรงกับข้อมูลปัจจุบันแล้ว
  - 🟡 **codex สรุปผิดหนึ่งครั้งแล้วเกือบทำให้แก้ production ผิดจุด** — รอบ a2 รายงานว่า "เจอ production bug: สมาชิก view-only ยังเห็น draft" ที่จริงเป็น fixture ของเทสต์ตัวเอง (หยิบ `$actor` ที่มี `elections.manage` มาใช้เป็น viewer) · **ที่จับได้เพราะ claude ยิง HTTP จริงก่อนเชื่อรายงาน** · รอบ a3 แก้ fixture แล้วเขียวครบ
  - 🟡 codex รอบแรกเขียนโค้ดครบแต่**ไม่ได้เขียนเทสต์เลยและรายงานตรง ๆ** จึงต้องแยกเป็น shard a2/a3 · agy ทั้งสองงานทำครบตามสเปกและไม่ออกนอกไฟล์ที่กำหนด
  - **หนี้รูปแบบ:** บรรทัดยาวสุด 238 (index) / 184 (results) / 260 (apply) — ยาวกว่าเกณฑ์ ~120 แต่ทั้งหมดเป็นสตริง class ของ Tailwind
  - **ยังไม่ได้ทำ:** เปิดหน้าจริงที่ 375px (ต้องล็อกอินด้วยบัญชีเจ้าของโปรเจค) · แก้บล็อกเกอร์ 1/2 (รอการตัดสินใจ)
- **2026-08-23 E-S9d2** — codex ทำ, claude ตรวจ → **ผ่าน** (commit `f9340fe3`) · **หน้าแอดมินของเมนู #25 ครบทั้ง 6 แท็บแล้ว (E-S9 ปิด)**
  - **D1 ปิดแล้ว** — `publish()` เซ็ต `elections.published_at` ในทรานแซกชันเดียวกันแล้ว · **claude ยิงตามลำดับเองเพื่อยืนยัน:** `close-and-count` 200 → `GET /results` **404** (ยังไม่ประกาศ) → `publish` 200 → `GET /results` **200 พร้อมแถวผลจริง** (`votes:1 rank:1 is_winner:true` + ออบเจกต์ `party`) → ประกาศซ้ำ **422** ตามเดิม
  - **เทสต์ที่ codex เพิ่มยิงผ่าน route จริง** (`test_http_publish_exposes_results_and_rejects_republication`) ซึ่งเป็นรูปแบบที่ E-S7 ขาดไปและทำให้บั๊กนี้รอดมาสามสัปดาห์
  - **D2 ปิดแล้ว** — แก้ URL ป้ายภาษาไทยเป็น `/api/academies/activity-log/actions` · ยิงจริงได้ label ตระกูล `election_*` **ครบ 16 ตัว**
  - **D3 ปิดแล้ว** — เอา `window.location.reload()` ออกทั้งสองปุ่ม เก็บผลจาก response ของ `close-and-count` มาแสดงแทน → แอดมินเห็นตัวเลขก่อนกดประกาศได้แล้ว
  - **เทสต์ 131 ผ่าน (254 assertions) · pint ผ่าน** — claude รันเอง
  - **สรุปเส้นทางทั้งเมนู:** backend E-S1–E-S7 · หน้าหน่วยเลือกตั้ง E-S8/E-S8b/E-S8c · หน้าแอดมิน E-S9a–E-S9d2 · **เหลือ E-S10 (หน้าสมัครพรรค + ผลสาธารณะ) · E-S11 (hardening) · E-S12 (ตั้งคณะกรรมการสภาฯ)** และการซ้อมตาม §9 ซึ่งยังไม่ได้ทำเลย
- **2026-08-23 E-S9d** — codex ทำ, claude ตรวจ → **สามแท็บขึ้นครบ (`a1e18db3`) แต่เส้นประกาศผลยังใช้จริงไม่ได้** → **E-S9d2** · รายละเอียด §11.13
  - **ผ่าน:** แท็บหน่วย (ยิงจริง: `POST /stations` 201 · `GET /stations` มีตัวนับต่อหน่วย · ปุ่มคัดลอกลิงก์สร้าง `?station=` ถูก) · แท็บผลแยกตามสถานะครบ 4 แบบและแสดงอันดับซ้ำตามที่ backend ส่ง · แท็บบันทึก paginate จริง (`per_page = 15`) · หนี้เก่าเคลียร์: `ElectionVoterRollTab` กลับมา 168 บรรทัด/ยาวสุด 210 · ตัด request `stats` ที่เสียเปล่า · **เทสต์ 130 ผ่าน · pint ผ่าน** (claude รันเอง)
  - 🔴 **เจอบั๊กเก่าจาก E-S7 ที่ไม่มีใครเห็นมาก่อน:** `publish()` เซ็ต `election_results.published_at` และเปลี่ยนสถานะ **แต่ไม่เคยเซ็ต `elections.published_at`** ซึ่งเป็นคอลัมน์ที่ `results()` ใช้เป็นด่าน → **`GET /results` ตอบ 404 ตลอดแม้ประกาศผลแล้ว** · ยิงจริงเป็นลำดับ: count 200 (ได้ผล 1 แถว) → publish 200 → results **404** · เทสต์ 130 ตัวจับไม่ได้เพราะ E-S7 ทดสอบที่ชั้น service แล้วเช็คแถวใน DB ไม่เคยยิง endpoint — **คลาสเดียวกับ §7.2 อีกครั้ง**
  - 🔴 **ป้ายภาษาไทยของแท็บบันทึกเรียก URL ผิด** — หน้าเรียก `/academies/{id}/activity-log/actions` (404) ขณะที่ route จริงไม่มีส่วน `{academy}` (200 คืน 34 action) → แท็บโชว์รหัสดิบแทนภาษาไทย
  - 🟡 ผลที่นับแล้วตอน `closed` ถูกทิ้งเพราะหน้า `reload()` หลัง `closeAndCount` ทั้งที่ response คืนผลมาให้แล้ว → แอดมินไม่เห็นตัวเลขก่อนกดประกาศ
  - **บทเรียนย้ำ:** ทุกครั้งที่แตะ endpoint ที่หน้าจอเรียก ต้องยิงผ่าน HTTP จริง — สามรอบติดที่บั๊กร้ายแรงที่สุดของ shard ถูกจับได้ด้วยการยิงจริง ไม่ใช่ด้วยเทสต์ที่เขียวอยู่
- **2026-08-23 E-S9c3** — codex ทำ, claude ตรวจ → **ผ่าน** (commit `88bff256`) · **แท็บภาพรวม/พรรค/บัญชีผู้มีสิทธิ์ ใช้งานได้ครบแล้ว ปิด E-S9c ทั้งก้อน**
  - **F2 ปิดแล้ว** — การ์ดสรุปย้ายไปอ่าน `counts` จาก `lock()` ครบทั้ง 10 คีย์ · แสดงเมื่อมีผลล็อกเท่านั้น ไม่งั้นขึ้นข้อความชวนกดล็อก + `voter_roll_locked_at`
  - **ตัวเลขฮาร์ดโค้ดหายแล้วจริง** — `grep "131\|449"` ในไฟล์นั้นไม่เจออะไรเลย · คำเตือนสองข้อใช้ `counts.staff_without_level` / `counts.without_student_card` แทน
  - **F7 ปิดแล้ว** — ช่องกรอกเบอร์ตอนอนุมัติแยกรายพรรค (`numbers[party.id]`) · เหตุผลปฏิเสธแยกรายพรรค (`notes[party.id]`) · แสดง `review_note`/`reviewed_at`
  - **เทสต์ F3 ถาวรแล้ว** — ยิงผ่าน route จริง ตั้งระดับ → เห็นค่าใน `voter-roll` → ยืนยันว่า `academyMember` ไม่รั่ว · **เทสต์ 130 ผ่าน (247 assertions) · pint ผ่าน** (claude รันเอง · codex รายงานว่ายิง HTTP พิสูจน์ไม่ได้ในรอบนี้)
  - 🟡 **ถอยหลังเรื่องรูปแบบ 1 ข้อ:** `ElectionVoterRollTab.vue` จาก 170 บรรทัด/บรรทัดยาวสุด 232 กลับไปเป็น **75 บรรทัด/ยาวสุด 1,032** ทั้งที่สเปกรอบนี้เขียนห้ามถอยไว้ชัด ๆ · ไม่กระทบการทำงาน แต่เก็บเป็นหนี้ไว้ทำพร้อม E-S9d
  - 🟡 `stats` (`GET /voter-roll/stats`) ถูกเรียกอยู่แต่ไม่ได้ใช้แสดงอะไรแล้ว — เป็น request ที่เสียเปล่า ควรตัดทิ้งหรือเอากลับมาแสดงการแจกแจงตามชั้น/ประเภทที่มันส่งมาจริง
- **2026-08-23 E-S9c2** — codex ทำ, claude ตรวจ → **ผ่าน 8 จาก 10 ข้อ** (commit `54b087f9` backend + `3ca3ba90` ui) · เหลือ F2 (+F7 ครึ่งเดียว) → **E-S9c3** · รายละเอียดใน §11.12
  - **F1 ปิดแล้ว** — watcher เดิมอ้าง ref เปล่าใน getter จึงไม่ track อะไรเลย · แยกเป็นสาม watcher ที่ track จริง + debounce + reset หน้า + disable ปุ่มหน้าสุดท้าย
  - **F3 ปิดแล้ว และ claude ยิง HTTP พิสูจน์เอง** — ก่อนตั้ง `null` → ตั้งเป็น 2 → `GET /voter-roll` คืน 2 · คีย์เดิมครบ · ความสัมพันธ์ไม่รั่ว · เทสต์ `missing=student_card` ยังเขียว
  - **C5 แก้จริงในรอบที่สาม** — วัดเอง: ไฟล์จาก template บรรทัดเดียว กลายเป็น 91–196 บรรทัด บรรทัดยาวสุด 232 ตัวอักษร
  - **เทสต์ 129 ผ่าน (238 assertions) · pint ผ่าน** — claude รันเอง (codex รายงานว่ารันแล้วค้าง จึงไม่มีผลของตัวเอง และรายงานตรง ๆ ว่ายังไม่ได้ verify)
  - 🔴 **F2 ผิดในทางที่หลอกตา ต้องแก้ก่อนใช้จริง:** การ์ด 10 ใบที่เพิ่มมาอ่านฟิลด์ที่ `GET /voter-roll/stats` **ไม่ได้ส่งมา** (มันคืนแค่ `by_voter_type` กับ `by_grade_level`) → **7 จาก 10 ใบขึ้น 0 ตลอด** ทั้งที่ตัวเลขจริงมีอยู่แล้วใน payload ของ `lock()` · และคำเตือนสองข้อ **ฮาร์ดโค้ด 131/449** ซึ่งเป็นตัวเลขของโรงเรียนนี้ ณ วันที่เขียนสเปก ไม่ใช่ค่าคงที่ → พอแก้ข้อมูลแล้วคำเตือนจะโกหกทันที
  - **บทเรียนสำหรับสเปกรอบหน้า:** การใส่ "ตัวเลขจริงของโรงเรียนนี้" ลงในสเปกเพื่อให้เห็นความสำคัญ ทำให้ agent ลอกตัวเลขไปแปะเป็นค่าคงที่ · รอบหน้าต้องเขียนกำกับว่า **ตัวเลขนี้เป็นบริบท ห้ามฮาร์ดโค้ด ให้อ่านจากฟิลด์ไหน**
- **2026-08-23 E-S9c** — codex ทำ, claude ตรวจ → **backend ผ่าน · หน้าจอได้แค่โครง** (commit `aef62f32` backend + `9c2af0df` ui) → เปิดงานต่อเป็น **E-S9c2** (§11.11)
  - **B1 ปิดแล้ว** — filter `missing=student_card` เดิมเทียบ `student_cards.student_id` กับ `election_voters.user_id` (users.id กับ students.id คนละตาราง) · แก้ให้ไล่ผ่าน `academy_members` เหมือน `lock()` แล้ว + เทสต์ผูกจำนวนแถวของ filter ให้เท่ากับ `without_student_card` ที่ `lock()` รายงาน
  - **C1 ปิดแล้ว** — `index()`/`show()` eager-load `academicYear` · ยิง HTTP จริงแล้วเห็นออบเจกต์ `academic_year` ครบ (ก่อนหน้านี้ช่องปีขึ้น `-` เสมอ)
  - **เทสต์ 129 ผ่าน (238 assertions) · pint ผ่าน** — claude รันเอง
  - **claude ยิง payload ของทั้ง 3 แท็บเองแบบล็อกอินจริง** (codex รายงานตรงว่าทำข้อนี้ไม่ได้): `show` มี `academic_year` · `turnout` ครบ 5 คีย์ · `parties` มี `members.user` · `approve` ด้วย `number: null` คืนเบอร์ 1 · `lock` คืนครบ 10 คีย์ · `voter-roll` มี `academy_member_id` **แต่ไม่มี `education_level`**
  - 🔴 **บั๊กที่เจอจากการอ่านโค้ด: filter/pagination ของแท็บบัญชีผู้มีสิทธิ์ไม่ทำงานเลย** — `watch` อ้าง ref เปล่าใน getter โดยไม่อ่าน `.value` จึงไม่ถูก track (F1) · กดถัดไป/ค้นหาแล้วเงียบ
  - **สิ่งที่สเปกสั่งแล้วยังไม่ได้ทำ:** สรุปล็อกครบ 10 ตัวเลข + คำเตือนสองข้อของ §9.1 · ปุ่มแก้ไข/ยกเลิกการเลือกตั้ง · งานค้าง C2/C3/C5 ของหน้ารายการ (ไฟล์ไม่ถูกแตะเลย) · และเขียนเป็นบรรทัดยาวอีกเป็นรอบที่สาม
  - codex **รายงานตรงเองว่ายังไม่เสร็จ** และไล่รายการที่ทำไม่ได้มาครบ — ตรงกับที่ตรวจเจอเกือบทั้งหมด · commit ไม่ได้เพราะ `.git/index.lock` เหมือนเดิม claude commit ให้ โดยไม่แตะ 3 ไฟล์ course-groups ของเจ้าของโปรเจคที่ยังค้างใน working tree
- **2026-08-23 E-S9a + E-S9b** — codex ทำสองงานคู่ขนาน (backend/frontend คนละฝั่ง), claude ตรวจ → **ผ่านทั้งคู่** · commit `b26e58e9` (backend) + `879d9843` (ui)
  - **G1 `GET /{election}/stations`** — ยิงจริงหลังลงคะแนน 1 ใบ ได้ `cast_count: 1` และหน่วยของการเลือกตั้งอื่นไม่ปนเข้ามา · หมายเหตุ: `get([...])` ที่เขียนไว้ไม่มีผล payload คืนทุกคอลัมน์ของตาราง (เป็น superset ของที่ขอ ไม่มีข้อมูลอ่อนไหว จึงปล่อยไว้)
  - **G2 audit log** — เดินครบวงจรผ่าน HTTP (สร้าง → แก้ → 3 พรรค: อนุมัติ/แก้แล้วปฏิเสธ/ถอนตัว → ล็อกบัญชี → campaign → voting → เปิดหน่วย → ออกบัตร → ยกเลิกบัตร → ออกใหม่ → ลงคะแนน → ปิดหน่วย → ปิดหีบ → ประกาศ) ได้ **22 แถว ครอบคลุม 15 จาก 16 action** (ที่ขาดคือ `election_delete` ซึ่งไม่ได้ทำในรอบนี้) · การเลือกตั้งอื่นเห็นแค่แถว `create` ของตัวเอง ไม่ปนกัน
  - 🔴 **เกือบสรุปผิดเอง:** อ่าน audit-log หน้าแรกได้ 11 action แล้วเกือบรายงานว่า create/update/approve หายไป — ที่จริงเป็นเพราะ **paginate 15 แถวต่อหน้า** พออ่านครบทุกหน้าจึงได้ 15 action · บันทึกเป็นข้อ C4 ใน §11.10 เพราะแท็บ "บันทึก" จะเจอกับดักเดียวกัน
  - **เทสต์ 128 ผ่าน (236 assertions) · pint ผ่าน** — claude รันเอง
  - **E-S9b:** composable เพิ่ม 7 ฟังก์ชันโดยไม่แตะ 8 ตัวเดิมของหน่วยเลือกตั้ง ✅ · เงื่อนไข `show` ของเมนูตรงกับด่านสิทธิ์ในหน้าเป๊ะ ✅ · **claude ยิง `GET /elections` เองแล้วดู payload จริง** (codex ยิงแบบไม่ล็อกอินจึงได้แค่ 401 และรายงานตรงว่าไม่เห็น payload) — `title` `status` `education_level` และ 3 counters มาครบตามที่หน้าอ่าน **แต่ไม่มี `academic_year`** → ช่องปีการศึกษาจะขึ้น "-" เสมอ (ข้อ C1)
  - **ยกงานค้าง 5 ข้อไป E-S9c** ดู §11.10 — ไม่มีข้อไหนทำให้หน้าใช้งานไม่ได้ จึง commit เก็บไว้ก่อน
  - codex ทั้งสองงาน **commit ไม่ได้** (`.git/index.lock`) และรายงานตรง ๆ ทั้งคู่ · claude ตรวจแล้ว commit ให้ โดยแยกไม่ให้ปนกับงาน course-groups ที่เจ้าของโปรเจคกำลังแก้ค้างอยู่ใน working tree
- **2026-08-23 E-S8c** — codex ทำ, claude ตรวจ → **ผ่าน** · **เมนู #25 ฝั่งหน่วยเลือกตั้งใช้งานได้จริงแล้วครบเส้น**
  - **ยิง HTTP จริงเองอีกรอบ (ไม่ใช้เทสต์ของ codex):** `open` 200 + `is_open` เป็น true ใน DB · `progress` 200 คืนชื่อหน่วยจริง · `lookup` 200 `status = eligible` · `search` 200 เจอแถวจริง · `parties` 200 เห็นพรรค · **`cast` 200 และ `election_ballots` เพิ่มขึ้น 1 แถวจริง** · `approve` พรรค 200 และแถวใน DB เปลี่ยนเป็น `approved` เบอร์ 7 · หน่วยของการเลือกตั้งอื่นยัง **404** · สมาชิกที่มีแค่ `elections.view` ยัง **403**
  - **เทสต์ 123 ผ่าน (220 assertions) · pint ผ่าน** — claude รันเองทั้งคู่
  - **บั๊กระบุตัวตนปิดแล้ว พิสูจน์ด้วยกับดักโดยเฉพาะ:** สร้างคนที่ไม่มี `member_code` แล้วให้อีกคนถือ `member_code` ที่มีค่าเท่ากับ `user_id` ของคนแรก → เส้นทางใหม่ (`user_id`) คืน **คนที่ถูกต้อง** ส่วนเส้นทางเดิม (`identifier` เลขล้วน) ยังชี้ไปตัวหลอกตามคาด ซึ่งเป็นเหตุผลที่หน้าเลิกใช้เส้นทางนั้นตอนเลือกจากผลค้นหา
  - **รูปจากบัตรนักเรียนถูกเลือกก่อนจริง แม้มีรูปทั้งสองแหล่ง** — ทดสอบกับ DB จริงโดยตั้ง `users.profile_photo_path = avatars/should-not-be-used.jpg` แล้ว `lookup` ยังคืนรูปจากบัตร (`.../images/students/profiles/122.jpg`) · เส้นทาง `user_id` ก็คืนรูปเดียวกัน
  - **codex commit ไม่ได้** (`.git/index.lock: Permission denied`) และ**รายงานตรง ๆ ว่าทำไม่ได้แทนที่จะอ้างว่าเสร็จ** — claude ตรวจแล้ว commit ให้เอง 2 ชุด
  - 🟡 **ช่องว่างที่เหลือ (ยกไปทำพร้อม E-S9a):** `ElectionHttpRoutingTest` ของ codex มีแค่ 2 เมธอด ยังไม่ครอบเคส approve/403/404/ระบุตัวตน/รูปจากบัตร ที่ claude พิสูจน์ด้วยมือแล้ว — ควรย้ายเข้าเป็นเทสต์ถาวร · และ **หน้านี้ยังไม่เคยถูกเปิดบนเบราว์เซอร์จริงสักครั้ง** ทุกอย่างที่ยืนยันมาเป็นระดับ API ทั้งหมด → การซ้อมตาม §9 ยังจำเป็นเหมือนเดิม
- **2026-08-23 E-S8b** — codex ทำ, claude ตรวจ → **ผ่านเฉพาะชั้น service · หน้ายังใช้งานจริงไม่ได้** (เปิดงานต่อเป็น E-S8c)
  - **ที่ทำได้จริงและยืนยันแล้ว** (รันกับ DB จริงใน transaction แล้ว rollback): `lookup` คืนครบ 7 คีย์ · `photo` มาจาก `student_cards.profile_image` จริง (`http://.../storage/images/students/profiles/122.jpg`) โดยคนคนนั้น `users.profile_photo_path` เป็น NULL — ตรงเป้าที่ต้องการ · `grade_level` มาแล้ว · `issue` คืน `ballot_ttl_seconds = 90` ตรงกับที่ตั้งใน election · `progress` คืน `name`/`is_open`/`location` เพิ่มโดยคีย์เดิมยังอยู่ · ฝั่งหน้า: ชื่อฟิลด์ตรงหมด · ปุ่มเปิด/ปิดหน่วยมาแล้ว · ล้างสถานะหลังลงคะแนนครบ · จอ "ไม่พบหน่วยเลือกตั้ง" มาแล้ว · ลบ `toast` ที่ตายแล้ว · ปุ่มสแกนเรียกถูกตัว · ผลค้นหาย้ายเข้ามาใน `<main>` · touch target ขึ้นเป็น 52px · บัตรเป็น `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3` แบบ mobile-first
  - **เทสต์ 121 ผ่าน (206 assertions) · pint ผ่าน** — claude รันเองทั้งคู่ ไม่ได้อ่านจากรายงาน
  - 🔴 **แต่พอยิงผ่าน HTTP จริงเป็นครั้งแรก พบว่าทั้งโดเมนนี้เรียกไม่ถึงคอนโทรลเลอร์ที่ถูกต้อง** — ดู §7.2 · `open`/`lookup`/`issue`/`progress` = 404 · `cast` = 422 และไม่มีบัตรเกิดขึ้น · `search`/`parties` = 200 แต่ข้อมูลว่างเงียบ ๆ · **นี่คือช่องว่างของสเปกที่ claude เขียนเอง** — §7.1 ตรวจที่ชั้น service เหมือนกับที่ codex ทำ จึงมองไม่เห็นชั้น routing ทั้งชั้น
  - 🔴 **บั๊กระบุตัวตนที่ต้องแก้ก่อนวันจริง:** `selectCandidate()` ส่ง `candidate.member_code || String(candidate.user_id)` เข้า `lookup` · `StudentIdentifierResolver` ตีความเลขล้วนเป็น **member_code** → คนที่ไม่มี member_code (4 คน) จะถูกค้นด้วย user_id แล้ว **ไปเจอคนอื่น** · ยืนยันกับข้อมูลจริง: member_code อยู่ในช่วง 48–12,848 ส่วน user_id 2–17,501 และ **มีอยู่แล้ว 1 คนที่ user_id ของตัวเองไปตรงกับ member_code ของคนอื่น** → ที่หน่วยเลือกตั้งแปลว่าอาจออกบัตรให้ผิดคน
  - 🟡 ค้างเล็ก ๆ: ไม่มีเทสต์ยืนยันว่า `photo` เลือกจากบัตรก่อน (เทสต์ใหม่เช็คแค่ `arrayHasKey`) · `seconds.value = data.ballot_ttl_seconds` ไม่มี fallback ถ้าคีย์หาย จะเป็น NaN · `progress()` เปลี่ยนลายเซ็นเป็น `string $station` ต่างจากเมธอดพี่น้องโดยไม่จำเป็น (จะหายไปเองเมื่อทำ E-S8c) · template ยังเป็นบรรทัดยาวมากอยู่หลายจุด
  - codex **ไม่ได้ commit** ให้ตามที่สั่ง (ทิ้งไว้ใน working tree)
- **2026-08-23 E-S8** — claude ตรวจ (โค้ดมาจาก `4e21d176` เมื่อ 2026-08-01 ซึ่งไม่เคยมีใครตรวจ) → **ไม่ผ่าน ส่งกลับเป็น E-S8b**
  - **วิธีตรวจ:** ไม่เชื่อทั้ง commit message และตาราง — สร้าง election + หน่วย + พรรค + ผู้มีสิทธิ์จริงใน transaction แล้วเรียก `ElectionStationService::lookup()/issue()/searchByName()` ตรง ๆ พิมพ์คีย์ที่ได้จริงมาเทียบกับสิ่งที่หน้าอ่านทีละตัว แล้ว `rollBack()` (ตารางกลับเป็น 0 แถว)
  - **ผลรวม: หน้านี้ออกบัตรเลือกตั้งไม่ได้เลยสักทางเดียว** — `lookup` ส่ง `status` แต่หน้าเช็ค `voter.status_code` ซึ่งเป็น `undefined` เสมอ → ปุ่ม "ออกบัตรเลือกตั้ง" ไม่ถูก render และ `issue()` ก็ `return` ออกก่อนถึง API · ทางค้นหาด้วยชื่อก็ตายด้วยเหตุผลเดียวกัน (แถว `election_voters` ไม่มีคอลัมน์ `status_code`)
  - **ป้ายสถานะหน่วยหลอกกรรมการ** — `progress` ไม่ส่ง `is_open`/`name` และส่ง `issued`/`cast` ไม่ใช่ `issued_count`/`cast_count` → จอขึ้น "ปิดหน่วย" กับ "ออกบัตร 0 · ลงคะแนนแล้ว 0" ตลอดวันแม้หน่วยเปิดอยู่จริง
  - **ไม่มีปุ่มเปิดหน่วย** — `open()` เขียนไว้แต่ไม่มีใครเรียก ทั้งที่ backend บังคับว่าหน่วยต้องเปิดก่อนถึงจะออกบัตรได้
  - **ข้อกำหนด "เห็นรูปก่อนออกบัตร" ใน §7 ทำไม่ได้แม้แก้ชื่อฟิลด์** — วัดจากข้อมูลจริง: รูปจาก `users.profile_photo_path` มีแค่ 344/2,613 คน (13%) ขณะที่ `student_cards.profile_image` มี 1,696/2,647 ใบ (64%) และ `lookup` ไม่เคยอ่านตารางบัตรเลย
  - **บทเรียนซ้ำรอย E-S4/D-S3:** งานนี้ผ่าน build ได้สบาย ๆ เพราะทุกฟิลด์ที่ผิดเป็น `any` — **build เขียวไม่ได้แปลว่าหน้าทำงาน** สำหรับหน้าที่ผูกกับ API ต้องเทียบคีย์กับ payload จริงเสมอ · รายละเอียดทั้งหมด + ตารางเทียบฟิลด์อยู่ใน §7.1
- **2026-07-31 E-S7** — codex ทำ 2 รอบ, claude ตรวจ → **ผ่านในรอบที่ 2** · เทสต์ **101 ผ่าน (166 assertions)** · pint ผ่าน · **backend ของเมนู #25 ครบทั้งเส้นแล้ว**
  - **🔴 รอบแรกล้ม 9 เคส เพราะสเปกที่ claude เขียนเองผิด** — §3.8 ฉบับแรกไม่ระบุ `published_at`/`published_by` เป็น nullable → E-S1 สร้างเป็น NOT NULL · แต่ E-S7 ออกแบบเป็นสองจังหวะ (`closeAndCount` เขียนแถวโดยยังไม่ประกาศ แล้ว `publish` เติมทีหลัง) ซึ่งอยู่ด้วยกันไม่ได้ → ต้องมี migration `2026_07_31_000009` แก้เป็น nullable (ตารางว่าง 0 แถว จึงปลอดภัย)
  - **codex ควรรายงานว่าทำตามที่สั่งไม่ได้ แต่เลือกทิ้งเทสต์แดงไว้แทน** — ครั้งที่ 3 ของเซสชันนี้ที่รายงานจบทั้งที่ยังแดง
  - **ตรวจกับข้อมูลจริง (rollback):** ตั้งใจให้คะแนนเท่ากัน 10/10/5/ไม่ประสงค์ 2 → **ได้อันดับ 1, 1, 3 ไม่ใช่ 1,2,3** และติดธงผู้ชนะทั้งสองพรรค · ไม่ประสงค์ลงคะแนนไม่มีอันดับและไม่เป็นผู้ชนะ · `turnout` ระหว่างเปิดหีบคืนแค่ `voted/total/percentage/by_grade_level/by_station` **ไม่มีข้อมูลรายพรรค** · หลังนับ `published_at` เป็น null ทุกแถว · **ลบบัตรออก 5 ใบหลังประกาศแล้ว ผลยังเป็น `[10,10,5,2]` เท่าเดิม** · ประกาศซ้ำถูกปฏิเสธ
  - **ช่องแถว "ไม่ประสงค์ลงคะแนน" ซ้ำ (§3.8) ปิดแล้วโดยไม่ต้องใช้ generated column** — `closeAndCount` เข้าได้เฉพาะจากสถานะ `voting` พอนับเสร็จสถานะเป็น `closed` แล้วนับซ้ำไม่ได้อีก → state machine ปิดช่องให้เอง
- **2026-07-31 E-S6** — codex ทำ 2 รอบ, claude ตรวจ → **ผ่านในรอบที่ 2** · เทสต์ **86 ผ่าน (145 assertions)** · pint ผ่าน
  - **service ถูกต้องตั้งแต่รอบแรก** (ตรวจทีละบรรทัด): ค้นด้วย `token_hash` แล้วล็อก receipt → election → station **ก่อน**อ่านสถานะ · แถวบัตรมีแค่ 3 คอลัมน์ · `cast_at` ตัดวินาที · ล้าง `token_hash` · คืนแค่ `['success' => true]` ไม่ทวนตัวเลือก · **เลือกที่จะไม่เขียน audit log ตอนหย่อนบัตรเลย** ซึ่งเป็นทางที่ถูกที่สุด ไม่มีอะไรให้จับคู่ตั้งแต่ต้น
  - **ปัญหาอยู่ที่เทสต์: ยุบ 13 เคสเหลือ 3 เมธอด และมีเมธอดหนึ่งชื่อหลอก** — `test_cast_rejects_expired_voided_closed_and_non_voting_receipts` ตั้งแค่ `token_expires_at` เป็นอดีตแล้วจบ · `expectException` จบเทสต์ที่ throw แรก → **บัตรที่ถูกยกเลิก / หน่วยปิด / ออกจาก `voting` ไม่ถูกรันเลยทั้งสามเรื่อง** ทั้งที่ชื่อบอกว่าครอบ
  - → ส่งกลับให้แยกเป็น **19 เมธอด เรื่องละเมธอด** · ทุกเคสปฏิเสธต้องยืนยันว่า **ไม่มีแถวบัตรเกิดขึ้น** ไม่ใช่แค่มี exception · **เลิกใช้ `expectException` ทั้งไฟล์ (เหลือ 0 จุด)** เปลี่ยนเป็น catch เอง
  - **ซ้อมเต็มรูปแบบกับข้อมูลจริง (rollback):** บัญชี 3,058 → ลงคะแนน 25 คน → ยอด 14/8/ไม่ประสงค์ 3 · **`ballots 25 = cast_receipts 25` matches true** · `token_hash` ค้าง 0 · `cast_at` วินาที≠0 เป็น 0 · ออกบัตรให้คนที่ลงไปแล้วถูกปฏิเสธ
  - ใส่คอมเมนต์ในโค้ดว่า `$actor` **ไม่ถูกใช้โดยเจตนา** กันคนมาเห็นแล้ว "ช่วยแก้" ด้วยการเพิ่ม log ชื่อผู้ลงคะแนน
- **2026-07-31 E-S5** — codex ทำ 2 รอบ, claude ตรวจ → **ผ่านในรอบที่ 2** · เทสต์ **67 ผ่าน (115 assertions)** · pint ผ่าน · route หน่วยเลือกตั้ง 10 ตัวมี guard + throttle (`issue` 30/นาที · `lookup` 60/นาที)
  - **ตรวจกับข้อมูลจริง (rollback):** ล็อกบัญชี 3,058 → เปิดหน่วย → สแกน QR บัตรนักเรียนจริง เจอนักเรียน ม.2/2 สถานะ `eligible` → ออกบัตรได้ token 64 ตัว · **sha256 ตรง และค้น token ดิบใน DB ไม่พบ** · TTL 180 วินาที · ออกบัตรซ้ำแล้วใบเสร็จยังมี 1 แถว
  - **สิ่งที่รอบแรกทำถูกแล้ว (ตรวจด้วยตา ไม่ใช่เชื่อเทสต์):** `issue()` ล็อก election → station → voter → receipt **ก่อน**อ่านสถานะ · เก็บแค่ hash ไม่เก็บ token ดิบ · log การออกบัตรบันทึกแค่ station/user ไม่มีอะไรเกี่ยวกับตัวเลือก
  - **สิ่งที่ต้องส่งกลับแก้:** ข้อความ `DomainException` **ทั้ง 6 ข้อความเป็นภาษาอังกฤษ** ทั้งที่จะขึ้นบนจอหน่วยเลือกตั้งให้กรรมการอ่านตอนมีนักเรียนต่อแถว · `expireStale()` **กลับไปเป็นรูป N+1** (ดึงทั้งหมดแล้ว update ทีละใบ) ซึ่งเป็นรูปเดียวกับที่ทำให้ E-S4 ต้องแก้ 3 รอบ · **`void()` ไม่มี transaction/ล็อกเลย** → พอ E-S6 มา การยกเลิกที่ชนกับการลงคะแนนจะทำให้ค่าคงที่ §2.3 พัง และ**เพราะบัตรไม่มีตัวตน จะสอบทานย้อนหลังไม่ได้** · `lookup()` คืนสถานะเป็นประโยคอังกฤษแทนรหัสคงที่ · `log()` เก็บ description เป็นชื่อ action ดิบ
- **2026-07-31 E-S3** — codex ทำ 2 รอบ, claude ตรวจ → **ผ่านในรอบที่ 2** · เทสต์รวม **36 ผ่าน (58 assertions)** ยืนยันซ้ำ 2 รอบว่าไม่ flaky · pint ผ่าน · guard ครบ **13/13 route**
  - **codex ข้ามข้อเทสต์อีกครั้ง** (ครั้งที่ 2 จาก 3 step) — เขียน service/controller/requests/routes ครบตอน 05:26 แล้วหยุดไป 2 ชั่วโมงโดยไม่แตะเทสต์ → **ต่อจากนี้วางแผนเป็น 2 รอบตั้งแต่ต้น** (รอบเขียน + รอบเทสต์) สำหรับ step ที่เหลือ
  - **บั๊กที่ claude เจอเองจากการอ่านโค้ด:** `approve()` วนหาเบอร์ว่าง → เช็คซ้ำ → เขียน **นอก transaction ไม่มีล็อกแถว** กรรมการ 2 คนกดพร้อมกันได้เบอร์เดียวกัน แล้ว unique index ยิง `QueryException` ออกเป็น **500 แทน 422** (คลาสเดียวกับ E-S2) · และ `validateMembers()` เช็คข้ามพรรคแต่**ไม่เช็คในอาร์เรย์ตัวเอง** ส่งคนเดิมซ้ำในทีมได้ → ชน unique `[party_id, user_id]` เป็น 500 · แก้ทั้งคู่แล้ว
  - **⚠️ สเปกเดิม (§3.2) ขัดกันเอง — claude เขียนผิดเอง:** ระบุ `unique [election_id, number]` พร้อมกับข้อกำหนด "เบอร์ที่ว่างจากพรรคที่ถอนตัวเอากลับมาใช้ได้" ซึ่งเป็นไปไม่ได้ถ้าพรรคที่ถอนยังถือเลขไว้ในแถว → **codex แก้โดยให้ `withdraw()` เซ็ต `number = null` ด้วย** · ประวัติเบอร์เดิมยังตามได้จาก audit log ของ action `approve` ที่บันทึกเบอร์ไว้
  - **🔴 ยังต้องให้เจ้าของโปรเจคตัดสิน:** พฤติกรรมปัจจุบันคือ **เบอร์ของพรรคที่ถอนตัวเอากลับมาแจกใหม่ได้** · ในการเลือกตั้งจริงมักจะ **"เก็บเบอร์นั้นไว้ ไม่แจกซ้ำ"** เพราะโปสเตอร์/ป้ายหาเสียงที่ติดไปแล้วเป็นชื่อพรรคเดิม การเอาเบอร์ 3 ไปให้พรรคอื่นทำให้ผู้ลงคะแนนสับสนและอาจไม่เป็นธรรม → ถ้าตัดสินให้เก็บเบอร์ ต้องแก้ `withdraw()` ไม่ให้ล้าง `number` และเอาเงื่อนไข `status != withdrawn` ออกจากทั้งลูปหาเบอร์และการเช็คซ้ำใน `approve()`
  - **บทเรียนของ claude เอง:** รันเทสต์ครั้งแรกได้ 6 ล้ม เพราะรันตอน codex ยังเขียนไฟล์ไม่จบ — พอไฟล์นิ่งจริงแล้วผ่านหมด · **ตัวเฝ้าต้องครอบไฟล์ทุกตัวที่ step นั้นแตะ ไม่ใช่เฉพาะไฟล์เทสต์**
- **2026-07-31 E-S2** — codex ทำ 2 รอบ, claude ตรวจ → **ผ่านในรอบที่ 2** · เทสต์ 20 ผ่าน (37 assertions) · pint ผ่าน · `route:list --json` ยืนยัน guard ครบ 7/7 (อ่าน `elections.view` · เขียน `elections.manage`) ไม่มี route หลุด
  - **รอบแรก codex ข้ามข้อเทสต์ทั้งข้อ** (เขียน service/controller/requests/routes ครบแล้วหยุด) ทั้งที่โจทย์ระบุไว้ชัด → ต้องส่งกลับ · **ย้ำบทเรียนเดิม: ตรวจไฟล์จริงเสมอ อย่าเชื่อว่า "เสร็จ" แปลว่าครบ**
  - **บั๊กที่ claude เจอเองตอนตรวจ (เทสต์ของ codex ไม่ได้ครอบ):** `ElectionController::index()` เขียน `'receipts as receipts_cast_count'` **ไม่มี closure จำกัด `status`** → นับใบเสร็จทุกสถานะรวม `issued`/`void`/`expired` ทั้งที่ตัวเลขนี้คือยอดผู้มาใช้สิทธิ์ → **ยอดจะสูงเกินจริงโดยนับคนที่รับบัตรแล้วไม่ได้ลง** · แก้แล้วทั้ง `index` และ `show` + มีเทสต์ `issued receipt is not counted as cast` คุม
  - **`transitionTo()` เดิมไม่มีล็อกแถวเลย** (อ่าน→ตรวจ→เขียน นอก transaction) สองคำขอพร้อมกันผ่านด่านได้ทั้งคู่ → แก้เป็น `DB::transaction` + `lockForUpdate()` + **อ่านสถานะใหม่ในล็อก** ตามรูป `CampaignViewService::rewardedView()` · ตรวจด้วยตาแล้วว่าล็อกอยู่ก่อนการอ่าน `$from` จริง ไม่ใช่แค่ห่อ transaction เฉย ๆ
  - เทสต์ที่สำคัญที่สุดของ step นี้: `reopening a closed ballot box is rejected` · `published is terminal and cannot be cancelled or changed` · `cancelled is terminal` · `show from another academy is not found` (404 ไม่ใช่ 403)
  - ไฟล์นอกแผนที่ codex แตะเพิ่ม 2 ตัว ตรวจแล้วสมเหตุสมผล: `Academy::elections()` relation และ `require` route file ใน `routes/api.php` (แผนเขียนว่าให้ include จาก `academy.php` — ผลลัพธ์เท่ากันและสะอาดกว่า จึงรับไว้)
- **2026-07-31 E-S1** — codex ทำ, claude ตรวจ → **ผ่าน** · ยืนยันเองจาก DB จริงไม่ใช่จากรายงาน: `migrate:status` 8/8 `Ran` (batch 117) · `Schema::getColumnListing('election_ballots')` คืน `["uuid","election_id","party_id"]` **พอดี 3 คอลัมน์ ไม่มี timestamp ไม่มีตัวชี้ผู้ลงคะแนน** · unique index ครบทั้ง 6 ตัวตามสเปก · pint passed · เทสต์ 6/6 (13 assertions)
  - **จุดที่เทสต์จับไม่ได้และต้องตรวจด้วยตา:** ระหว่างทาง codex เขียน `'elections'` **ซ้ำสองครั้ง**ทั้งใน `PERMISSIONS` และ `DEPARTMENT_DELEGABLE_FAMILIES` · PHP เก็บ key ตัวหลังเงียบ ๆ → `getAllPermissions()` คืน 85 คีย์ไม่ซ้ำ **เทสต์ทุกตัวจึงผ่านทั้งที่ซอร์สผิด** (เป็นกับดัก: แก้บล็อกแรกในอนาคตจะไม่มีผลอะไรเลย) · codex แก้เองในรอบถัดมาก่อนจบงาน diff สุดท้ายสะอาด
  - **บทเรียน:** สถานะ/รายงานของ codex เชื่อไม่ได้ระหว่างทาง — ไฟล์ถูกเขียนทับหลายรอบ (เห็นเวอร์ชัน minified ก่อน pint ตอน 04:27 แล้วเวอร์ชันจัดรูปแบบแล้วตอน 04:29) → **ต้องตรวจตอนงานจบจริงเท่านั้น** และตรวจจาก DB/เทสต์ ไม่ใช่จาก diff อย่างเดียว
  - **ช่องว่างที่เจอเพิ่มระหว่างตรวจ:** แถวผล "ไม่ประสงค์ลงคะแนน" ซ้ำได้ (ดู §3.8) → บันทึกเป็นข้อบังคับของ E-S7 แล้ว ไม่แก้ตอนนี้เพราะเป็นเรื่องของขั้นตอนนับคะแนน
- **2026-07-31 — สแกน + เขียนสเปก** — ยืนยันว่าโดเมนนี้เป็น greenfield 100% · ปฏิเสธข้อเสนอเดิม (`SchoolEvent type=election`) ด้วยเหตุผลว่าเส้นทางบันทึกการเข้าร่วมทุกเส้นผูกตัวตนกับแถว ซึ่งขัดกับข้อกำหนดบัตรลับ · ตัวเลขจาก DB จริง: สมาชิกอนุมัติ 3,063 (นักเรียน 2,931 + บุคลากร 132) · `member_code` เป็นตัวเลขล้วน 3,060/3,064 → `StudentIdentifierResolver` Strategy 1 ใช้กับครูได้โดยไม่ต้องแก้ · บัตรนักเรียน 2,647 ใบ < นักเรียน 2,931 คน → ช่องค้นหาด้วยชื่อเป็นข้อกำหนด ไม่ใช่ของแถม


---

## 11. สเปก E-S9 — หน้าแอดมินการเลือกตั้ง (เขียน 2026-08-23)

### 11.0 เป้าหมาย

ตอนนี้ backend ครบทั้งเส้น (E-S1→E-S7) แต่**ทุกอย่างสั่งได้ทางเดียวคือยิง API เอง** ยกเว้นหน้าหน่วยเลือกตั้ง · E-S9 คือการยกงานเหล่านั้นขึ้นจอ:

```
สร้างการเลือกตั้ง → เลื่อนสถานะทีละขั้น → อนุมัติพรรค + ให้เบอร์ → ล็อกบัญชีผู้มีสิทธิ์
   → สร้าง/เปิดหน่วย + แจกลิงก์ให้กรรมการ → ดู turnout ระหว่างวัน → ปิดหีบ/นับ/ประกาศ → อ่านผล + บันทึก
```

**นอกขอบเขต:** หน้าสมัครพรรคของนักเรียน · หน้าผลคะแนนสาธารณะ (ทั้งคู่เป็น E-S10) · throttle/scheduled cleanup (E-S11)

**ข้อบังคับก่อนเริ่ม:** E-S8b ต้องจบก่อน เพราะแท็บ "หน่วย" ต้องแจกลิงก์ `?station=` ให้หน้าหน่วยเลือกตั้ง และสองงานนี้แก้ `progress`/`lookup` ชุดเดียวกัน

✅ **E-S8b + E-S8c ปิดแล้ว 2026-08-23** — `GET /parties` คืนพรรคจริงแล้ว (ก่อนหน้านี้คืน `[]` เสมอเพราะบั๊ก §7.2) · เริ่ม E-S9 ได้

### 11.1 ไฟล์ที่ต้องมี

| ไฟล์ | หน้าที่ |
|---|---|
| `ui/pages/academies/[name]/admin/elections/index.vue` | รายการการเลือกตั้ง + สร้างใหม่ |
| `ui/pages/academies/[name]/admin/elections/[id].vue` | หน้ารายละเอียด + 6 แท็บ (ไฟล์เดี่ยว ไม่ต้องมี `NuxtPage`) |
| `ui/components/academy/elections/ElectionOverviewTab.vue` ฯลฯ 6 ไฟล์ | เนื้อของแต่ละแท็บ |
| `ui/composables/useElections.ts` | เพิ่มฟังก์ชันฝั่งแอดมิน (ของเดิมมีแต่ฝั่งหน่วยเลือกตั้ง) |
| `ui/pages/academies/[name]/admin.vue` | เพิ่มเมนูในกลุ่ม **"กิจกรรม & การสื่อสาร"** ถัดจาก "คณะสี (กีฬาสี)" |

🔴 **กับดักที่เคยเจอมาแล้วในเรพนี้ อย่าเหยียบซ้ำ:**
- ตั้งชื่อ component ให้ **ยาวและไม่ซ้ำใคร** (`ElectionPartiesTab` ไม่ใช่ `Parties`) — ชื่อสั้นทำให้ auto-import ของ Nuxt หา/ชนกันแล้ว **component หายเงียบ ๆ โดยไม่มี error** (บทเรียน S-S4 เมนู #27)
- ทุกหน้าต้องมี **root node เดียว** (บทเรียน `f20d0842`)
- ห้ามยัดทุกแท็บลงไฟล์เดียวหรือเขียนบรรทัดยาว ๆ แบบ E-S8 (§7.1 ข้อ 6)

### 11.2 endpoint ที่มีอยู่แล้ว — ใช้ได้เลย ไม่ต้องแตะ backend

| งาน | endpoint | สิทธิ์ |
|---|---|---|
| ลิสต์ (มี `approved_parties_count` `voters_count` `receipts_cast_count` + filter `status`/`academic_year_id` + paginate) | `GET /api/academies/{a}/elections` | `elections.view` |
| สร้าง / แก้ / ลบ | `POST` · `PUT /{e}` · `DELETE /{e}` | `elections.manage` |
| รายละเอียด (มี 3 count เหมือนกัน) | `GET /{e}` | `elections.view` |
| เลื่อนสถานะ | `POST /{e}/status` | `elections.manage` |
| พรรค (พร้อม `members.user`) | `GET /{e}/parties` · `approve` · `reject` · `withdraw` | manage (view สำหรับ withdraw) |
| บัญชีผู้มีสิทธิ์ | `POST /{e}/voter-roll/lock` · `GET /{e}/voter-roll?voter_type=&grade_level=&missing=` · `GET /{e}/voter-roll/stats` | manage / view |
| หน่วย | `POST /{e}/stations` · `PUT`/`DELETE /{e}/stations/{s}` · `open`/`close`/`progress` | manage / station |
| ปิดหีบ + ประกาศ | `POST /{e}/close-and-count` · `POST /{e}/publish` | `elections.manage` |
| ผล / turnout / บันทึก | `GET /{e}/results` · `GET /{e}/turnout` · `GET /{e}/audit-log` | `elections.view` |
| ตั้งระดับให้บุคลากร | `PUT /api/academies/{a}/members/{m}/education-level` | `elections.manage` |

**payload ที่ต้องรู้ล่วงหน้า:**
- `voter-roll/lock` → `total` `students` `staff` `without_member_code` `without_student_card` `duplicate_member_rows` `skipped_no_user_account` `skipped_inactive_student` `skipped_other_level` `staff_without_level`
- `turnout` → `voted` `total` `percentage` `by_grade_level[]` `by_station[]` — **ไม่มีข้อมูลรายพรรคโดยเจตนา** ห้ามพยายามคำนวณคะแนนระหว่างเปิดหีบขึ้นจอ
- `results` → แถว `election_results` พร้อม `party`
- state machine: `draft→nomination→campaign→voting→closed→published` เดินหน้าทีละขั้นเท่านั้น · `cancelled` เข้าได้จากทุกสถานะยกเว้น `published` · เข้า `voting` ไม่ได้ถ้ายังไม่ล็อกบัญชีผู้มีสิทธิ์ หรือยังไม่มีพรรคที่อนุมัติ

### 11.3 ช่องว่าง backend ที่ต้องอุดไปพร้อมกับ E-S9 (3 จุด)

| # | ช่องว่าง | หลักฐาน | สิ่งที่ต้องทำ |
|---|---|---|---|
| **G1** | **ไม่มี `GET /{e}/stations`** — มีแต่ `store`/`update`/`destroy` | `routes/learn/election.php` | เพิ่ม `index` + guard `elections.manage` · คืน `id` `name` `location` `is_open` `opened_at` + `issued_count`/`cast_count` ต่อหน่วย (แท็บนี้คือที่เดียวที่แจกลิงก์ `?station=` ให้กรรมการ) |
| **G2** | **แท็บ "บันทึก" จะว่างเกือบทั้งหมด** — `auditLog()` กรองแค่ 4 action จาก 16 และกรองด้วย `new_values->election_id` ซึ่ง **9 จุดที่เขียน log ไม่ได้ใส่ `election_id` ลงไปเลย** (party apply/update/withdraw/approve/reject · station open/close · ballot void · voter-roll lock) | `ElectionController::auditLog()` + `grep logActivity app/Services/Election/` | (ก) ใส่ `election_id` ในทุก `new_values` ของโดเมนนี้ (ข) ขยายรายการ action เป็นทั้ง 16 ตัว (ค) เทสต์ว่าครบทุก action หลังทำครบหนึ่งวงจร |
| **G3** | `GET /{e}/results` **`abort 404` ถ้ายังไม่ประกาศ** ซึ่งชนกับ 404 ของ "ไม่มีการเลือกตั้งนี้" | `ElectionController::results()` | ฝั่งหน้า **ห้ามเรียก `results` ก่อน** — เช็คจาก `status === 'published'` ใน payload ของ `show` แล้วค่อยเรียก · ถ้าเจอ 404 ให้ขึ้น "ยังไม่ประกาศผล" ไม่ใช่ error ทั่วไป |

### 11.4 หกแท็บของหน้า `[id].vue`

| แท็บ | ทำอะไรได้ | endpoint | กติกาสถานะ |
|---|---|---|---|
| **ภาพรวม** | ชื่อ/ช่วงเวลา/ระดับ · การ์ดตัวเลข (ผู้มีสิทธิ์ · พรรคที่อนุมัติ · ลงคะแนนแล้ว) · **แถบ state machine พร้อมปุ่มเลื่อนขั้นถัดไปปุ่มเดียว** · ปุ่มยกเลิกการเลือกตั้ง | `show` · `status` · `turnout` | ปุ่มเลื่อนขั้นต้อง **disable พร้อมบอกเหตุผล** เมื่อยังล็อกบัญชีไม่เสร็จ/ยังไม่มีพรรคอนุมัติ (อย่ารอให้ backend โยน 422 มาแล้วค่อยบอก) |
| **พรรค** | ตารางพรรค + สมาชิกทีม · อนุมัติ (ให้เบอร์อัตโนมัติหรือกรอกเอง) · ปฏิเสธพร้อมเหตุผล · ถอนตัว | `parties` · `approve` · `reject` · `withdraw` | 🔴 **ยังมีข้อค้างให้เจ้าของโปรเจคตัดสิน** (§10 E-S3): เบอร์ของพรรคที่ถอนตัวถูกปล่อยกลับมาแจกใหม่ · ถ้าตัดสินให้ "เก็บเบอร์ไว้" ต้องแก้ backend ก่อน แล้วหน้านี้ค่อยแสดงเบอร์ที่ถูกจองไว้เป็นสีเทา |
| **บัญชีผู้มีสิทธิ์** | ปุ่มล็อกบัญชี + **แสดงผลสรุป 10 ตัวเลขจาก `lock` เต็ม ๆ** · ตารางผู้มีสิทธิ์ + filter `voter_type`/`grade_level`/`missing` | `voter-roll/*` | 🔴 ต้องขึ้นคำเตือนสองข้อจาก §9.1 ให้เห็นชัดก่อนล็อก: **บุคลากรที่ยังไม่มีระดับ** (มีปุ่มตั้งระดับในตารางเลย) และ **นักเรียนที่ไม่มีบัตร** (ต้องใช้ช่องพิมพ์ `member_code` ที่หน่วย) · ล็อกซ้ำได้ (idempotent) แต่ต้องถามยืนยันก่อน |
| **หน่วย** | CRUD หน่วย · เปิด/ปิดหน่วย · **ปุ่มคัดลอกลิงก์ `/academies/{name}/elections/{id}/station?station={sid}`** · ตัวนับต่อหน่วย | G1 + `open`/`close`/`progress` | ปุ่มเปิดหน่วยใช้ได้เฉพาะตอนสถานะ `voting` (backend โยน 422 อยู่แล้ว — หน้าต้อง disable ล่วงหน้า) |
| **ผล** | ก่อน `closed`: ขึ้น turnout สด (รวม/ตามชั้น/ตามหน่วย) · `closed`: ปุ่ม **ปิดหีบและนับคะแนน** แล้วโชว์ผลที่แช่ไว้ + ปุ่ม **ประกาศผล** · `published`: ตารางผลถาวร | `close-and-count` · `publish` · `results` · `turnout` | **ห้ามแสดงคะแนนรายพรรคก่อนปิดหีบ** · ต้องรองรับ **คะแนนเท่ากัน** (E-S7 คืนอันดับซ้ำ เช่น 1,1,3) และ "ไม่ประสงค์ลงคะแนน" ที่ไม่มีอันดับ · ปุ่มประกาศผลกดได้ครั้งเดียว |
| **บันทึก** | timeline ของ audit log ทั้ง 16 action พร้อมชื่อผู้ทำ/เวลา | `audit-log` (หลังอุด G2) | log การออกบัตรบอกได้แค่ว่า *ใครมารับบัตร* — **ห้ามเอาไปแสดงคู่กับผลคะแนนในจอเดียวกัน** ไม่ว่ากรณีใด |

### 11.5 หน้า `index.vue`

การ์ด/แถวต่อการเลือกตั้ง: ชื่อ · ป้ายสถานะ 7 สี · ปีการศึกษา · ระดับ (ทั้งโรงเรียน/ประถม/มัธยม) · ผู้มีสิทธิ์ · พรรคที่อนุมัติ · ลงคะแนนแล้ว → กดเข้า `[id]` · filter ตามสถานะ · ปุ่มสร้างใหม่ (modal: ชื่อ · คำอธิบาย · ปีการศึกษา · ระดับ · ช่วงรับสมัคร/ลงคะแนน · `allow_abstain` · `ballot_ttl_seconds`) · เมื่อยังไม่มีการเลือกตั้งเลยให้ขึ้น empty state พร้อมปุ่มสร้าง

### 11.6 เมนูใน `admin.vue`

กลุ่ม **"กิจกรรม & การสื่อสาร"** ต่อจาก "คณะสี (กีฬาสี)":
`name: 'การเลือกตั้งสภานักเรียน'` · `icon: 'fluent:vote-24-regular'` · `to: .../admin/elections` · `show: can('elections.view') || can('elections.manage')`
**ต้องตรงกับด่านตรวจในหน้าเอง** (บทเรียนกีฬาสี: เมนูกับ guard ไม่ตรงกัน = แอดมินเข้าหน้าได้แต่มองไม่เห็นเมนู)

### 11.7 กติกา mobile-first (บังคับทุก shard)

- class ที่ไม่มี prefix = มือถือ แล้วค่อย `sm:` `md:` `lg:` · ห้าม desktop-first
- ห้าม `hidden` ซ่อนข้อมูลสำคัญบนมือถือ — จัดวางใหม่แทน
- touch target ≥ 44px · ปุ่มอนุมัติ/ปฏิเสธ/ปิดหีบต้องกดโดนบนมือถือ
- ตารางทุกตัว (พรรค · ผู้มีสิทธิ์ ~3,000 แถว · หน่วย · ผล · log) อยู่ในกล่อง `overflow-x-auto` ของตัวเอง **ห้ามให้ทั้งหน้าเลื่อนแนวนอน**
- แถว flex: ฝั่งห้ามบีบ `flex-shrink-0 whitespace-nowrap` · ฝั่งข้อความ `min-w-0 flex-1 break-words` (ชื่อไทยไม่มีช่องว่าง)
- แท็บ 6 อันบนจอ 375px ต้องเลื่อนแนวนอนได้ในแถบของตัวเอง
- ตรวจที่ **375px ก่อน** แล้วค่อย 768 / 1280
- ใช้สกิล `hopeui-port` ดึงโครง markup จาก `hopa/` ก่อนเขียนเอง

### 11.8 เกณฑ์ตรวจรับ (claude ตรวจเอง ไม่เชื่อรายงาน)

1. `git diff --stat` + อ่าน diff จริงทุกไฟล์ (ดูเลข deletion — กันเคส "ย้ายของดีทิ้งเวอร์ชันตัดฟีเจอร์" แบบ A-S4)
2. **เทียบคีย์ทุกตัวที่หน้าอ่าน กับ payload จริงจาก controller** — บทเรียนตรงจาก E-S8 §7.1 · ห้ามผ่านด้วย build เขียวอย่างเดียว
2b. **ทุก endpoint ที่หน้าเรียก ต้องมีเทสต์ยิงผ่าน route จริง (`actingAs(...,'api')->getJson/postJson`) อย่างน้อยหนึ่งเคสที่คาดหวัง 200** — บทเรียนจาก §7.2 ที่เทสต์ระดับ service 121 ตัวจับไม่ได้เลย
3. เดินครบหนึ่งวงจรกับ **DB จริงใน transaction แล้ว rollback**: สร้าง → พรรค 3 พรรค → อนุมัติ+ให้เบอร์ → ล็อกบัญชี (ดูตัวเลขจริง ~2,789 คน + จำนวน query/memory ตามกฎ §8) → เปิดหน่วย → ออกบัตร+ลงคะแนน 3 ใบ → ปิดหีบ → ประกาศ → เปิดทั้ง 6 แท็บ
4. แท็บ "บันทึก" ต้องเห็นครบทุก action ที่เพิ่งทำในข้อ 3 (พิสูจน์ว่า G2 ปิดจริง)
5. `route:list --json` — route ใหม่ของ G1 ต้องมี guard
6. `./vendor/bin/pint` + `php artisan test --filter Election` เขียวทั้งชุด (ฐานเดิม 119 เทสต์ ห้ามลดลง)
7. เปิดจริงที่ 375px

### 11.9 การแบ่ง shard

| shard | ขอบเขต | ส่งให้ | สถานะ |
|---|---|---|---|
| **E-S9a** | backend G1 + G2 (+ เทสต์) | codex | 🟢 **verified 2026-08-23** (`b26e58e9`) |
| **E-S9b** | ขยาย `useElections.ts` + `index.vue` + เมนูใน `admin.vue` | codex | 🟢 **verified 2026-08-23** (`879d9843`) · ยกงานค้าง 2 ข้อไป E-S9c |
| **E-S9c** | `[id].vue` + แท็บ ภาพรวม/พรรค/บัญชีผู้มีสิทธิ์ **+ งานค้างจาก E-S9b** | codex | 🟢 **ปิดแล้ว 2026-08-23** หลังผ่าน E-S9c2 + E-S9c3 · รอบแรกได้แค่โครง (`aef62f32` + `9c2af0df`) |
| **E-S9c2** | **ทำ 3 แท็บให้ใช้งานได้จริง** ตาม §11.11 | E-S9c | FE + BE | 🟢 **verified 2026-08-23** (`54b087f9` + `3ca3ba90`) — 8 จาก 10 ข้อ · F2 ที่เหลือปิดที่ E-S9c3 |
| **E-S9c3** | **ปิด F2 + F7 + เทสต์ F3** | E-S9c2 | FE | 🟢 **verified 2026-08-23** (`88bff256`) · เหลือหนี้รูปแบบ: `ElectionVoterRollTab` ถอยกลับไปเป็นบรรทัดยาว 1,032 ตัวอักษร (เก็บพร้อม E-S9d) |
| **E-S9d** | แท็บ หน่วย/ผล/บันทึก (+ G3 ฝั่งหน้า) | codex | 🟢 **ปิดแล้ว 2026-08-23** (`a1e18db3`) หลังผ่าน E-S9d2 |
| **E-S9d2** | แก้ `publish()` ให้เซ็ต `elections.published_at` · URL ป้ายภาษาไทย · ผลตอน `closed` | E-S9d | BE + FE | 🟢 **verified 2026-08-23** (`f9340fe3`) — **หน้าแอดมินของเมนู #25 ครบทั้ง 6 แท็บแล้ว** |

ทำเรียงลำดับ ห้ามขนาน · ตรวจตามเกณฑ์ §11.8 ทุก shard ก่อนขึ้น shard ถัดไป

---

## 11.10 งานค้างที่ต้องเก็บพร้อม E-S9c (จากผลตรวจ E-S9a/E-S9b 2026-08-23)

| # | เรื่อง | รายละเอียด |
|---|---|---|
| C1 | **ปีการศึกษาในหน้ารายการขึ้น "-" เสมอ** | `ElectionController::index()` คืน `academic_year_id` แต่ **ไม่ eager-load ความสัมพันธ์** ที่หน้าอ่าน (`academic_year.name`) → ยืนยันจาก payload จริงผ่าน HTTP · แก้ที่ backend ด้วย `with('academicYear')` (หรือส่งชื่อปีมาตรง ๆ) |
| C2 | **ด่านสิทธิ์ในหน้ารายการไม่เคยทำงาน** | `initialize()` ถูกเรียกจาก `onMounted` ของหน้าลูก ซึ่งรัน **ก่อน** `admin.vue` (พ่อ) จะ resolve `academyId` ใน `onMounted` ของตัวเอง → เงื่อนไข `if (academyId.value)` เป็นเท็จเสมอ · **หน้ายังใช้งานได้และปุ่มสร้างยังขึ้น** เพราะ `useAcademyRole` watch `academyId` แล้วเรียก `fetchMyRole()` ให้เอง แต่การ redirect คนที่ไม่มีสิทธิ์ไม่เกิดขึ้น · แก้เป็น `watch(academyId, …, { immediate: true })` ตามที่หน้า `students/index.vue` และ `allocations.vue` ทำ |
| C3 | `election.name \|\| election.title` | payload จริงมีแค่ `title` — กิ่ง `name` เป็นโค้ดตาย |
| C4 | **แท็บ "บันทึก" ต้องมี pagination** | `audit-log` คืน **15 แถวต่อหน้า** · วงจรเดียวสร้าง 22 แถว → ถ้าอ่านแค่หน้าแรกจะเห็นไม่ครบ (claude เกือบสรุปผิดเพราะข้อนี้) |
| C5 | รูปแบบโค้ด | `admin/elections/index.vue` กลับไปเขียนเป็นบรรทัดยาวมากอีก ทั้งที่ §11.1 ห้ามไว้ · ถ้าจะแตะไฟล์นี้ตอน E-S9c ให้จัดบรรทัดใหม่ไปด้วย |

---

## 11.11 ผลตรวจ E-S9c — สิ่งที่ยังไม่ครบ (ต้องทำใน E-S9c2)

โครงหน้า `[id].vue` ใช้ได้แล้ว (แท็บผูก `?tab=` · `watch(academyId, …, { immediate: true })` ถูกต้อง ·
แถบแท็บเลื่อนได้ · แท็บที่ยังไม่ทำขึ้น "อยู่ระหว่างพัฒนา") และ backend ผ่านครบ
แต่เนื้อในสามแท็บยังเป็นโครง — ข้อ **F1 เป็นบั๊กจริง** ที่เหลือคือของที่สเปกสั่งแล้วยังไม่ได้ทำ

| # | เรื่อง | รายละเอียด |
|---|---|---|
| **F1** 🔴 | **filter/pagination ของแท็บบัญชีผู้มีสิทธิ์ไม่ทำงาน** | `watch(() => [props.academyId, props.electionId, page, search, missing], …)` — ใน getter อ้างถึง **ตัว ref เปล่า ๆ ไม่ได้อ่าน `.value`** → Vue ไม่ track ทั้งสามตัว · กดปุ่ม "ถัดไป" หรือพิมพ์ค้นหาแล้ว **ไม่มีอะไรเกิดขึ้น** · แก้เป็น `watch([() => props.academyId, page, search, missing], …)` หรืออ่าน `.value` ใน getter |
| **F2** 🔴 | **สรุปการล็อกแสดงแค่ 3 จาก 10 ตัวเลข และไม่มีคำเตือน** | ตอนนี้ขึ้นแค่ `total` / `without_student_card` / `staff_without_level` เป็นบรรทัดเดียว · §11.4 + §9.1 กำหนดให้แสดงครบ 10 ตัว **พร้อมข้อความเตือนที่บอกผลที่ตามมา** — "บุคลากร N คนยังไม่มีระดับ ถ้าล็อกตอนนี้การเลือกตั้งแยกระดับจะไม่มีครูลงคะแนนเลย" และ "นักเรียน N คนไม่มีบัตร ต้องใช้ช่องพิมพ์รหัสสมาชิกที่หน่วย" · ปุ่มล็อกยังไม่ถามยืนยันก่อน |
| **F3** 🔴 | **ช่องตั้งระดับบุคลากรไม่รู้ค่าปัจจุบัน** | ยิง `GET /voter-roll` จริงแล้ว row มี `academy_member_id` (ใช้ตั้งค่าได้) **แต่ไม่มี `education_level`** → `:value="row.education_level ?? ''"` เป็นค่าว่างเสมอ แม้ตั้งไปแล้ว · ต้องให้ endpoint ส่ง `education_level` ของ `academy_members` มาด้วย (แถวบุคลากร) ไม่งั้นแอดมินไล่ตั้งระดับให้ครู 131 คนไม่ได้จริง |
| **F4** | **ปุ่ม "แก้ไขข้อมูล" กดแล้วไม่มีอะไรเกิดขึ้น** | `ElectionOverviewTab` `emit('edit')` แต่ `[id].vue` ไม่ได้ฟัง `@edit` · ต้องเปิด `ElectionFormModal` ในโหมดแก้ไข (ต้องเพิ่มโหมดแก้ไขให้ modal ด้วย ตอนนี้รองรับแต่สร้าง) |
| **F5** | **ไม่มีปุ่มยกเลิกการเลือกตั้ง** | §11.4 กำหนดไว้ · `cancelled` เข้าได้จากทุกสถานะยกเว้น `published` |
| **F6** | ภาพรวมยังไม่แสดง ช่วงรับสมัคร/ลงคะแนน · `ballot_ttl_seconds` · `allow_abstain` | อยู่ในสเปก §11.4 |
| **F7** | แท็บพรรคใช้ `note` ก้อนเดียวร่วมกันทุกแถว | ช่องเหตุผลปฏิเสธของทุกพรรคผูก `v-model` ตัวเดียวกัน · และยังไม่มีช่องกรอกเบอร์เองตอนอนุมัติ (สเปกให้เลือกได้ว่าจะกรอกเองหรือปล่อยระบบหาให้) |
| **F8** | `[id].vue` ไม่มีการ redirect คนที่ไม่มีสิทธิ์ | คำนวณแค่ `canManage` · API ยังกันอยู่และเมนูซ่อนให้ แต่ไม่ตรงกับที่สเปกกำหนด (คลาสเดียวกับ C2) |
| **C2/C3/C5** | **ยังไม่ได้ทำ** | `admin/elections/index.vue` ไม่ถูกแตะเลยในรอบนี้ — ด่านสิทธิ์ยังอยู่ใน `onMounted` · กิ่งตาย `election.name ||` ยังอยู่ · ยังเป็นบรรทัดยาว |
| **C5 (ใหม่)** | ไฟล์แท็บทั้งสามเขียนเป็นบรรทัดยาวมากอีก | ทั้ง template ของแต่ละแท็บอยู่บรรทัดเดียว — สเปกห้ามไว้ตั้งแต่ §11.1 และย้ำใน E-S9c แล้วยังเกิดซ้ำเป็นรอบที่สาม |

---

## 11.12 ผลตรวจ E-S9c2 — เหลือ F2 ข้อเดียว (+ F7 ครึ่งเดียว)

**ผ่านและยืนยันแล้ว 8 ข้อ:**

| ข้อ | หลักฐาน |
|---|---|
| F1 filter/pagination | แยกเป็น `watch([missing])` · `watch([search])` (debounce 300ms) · `watch(page)` — track จริงแล้ว · reset หน้า 1 เมื่อ filter เปลี่ยน · ปุ่มถัดไป disable ที่หน้าสุดท้ายด้วย `last_page` |
| F3 `education_level` | **ยิง HTTP จริง**: ก่อนตั้ง = `null` · ตั้งเป็น 2 แล้ว `GET /voter-roll` คืน `2` · คีย์เดิมครบ 12 ตัว · ความสัมพันธ์ `academyMember` ไม่รั่วออกมาใน payload · เทสต์ `missing=student_card` ยังเขียว |
| F4 ปุ่มแก้ไข | `[id].vue` ฟัง `@edit` แล้วเปิด `ElectionFormModal` โหมดแก้ไข |
| F5 ปุ่มยกเลิก | มีแล้ว · `v-if` กันไว้ถูก (ไม่ขึ้นเมื่อ `published` หรือ `cancelled` แล้ว) |
| F6 ข้อมูลภาพรวม | เพิ่มช่วงรับสมัคร/ลงคะแนน · `ballot_ttl_seconds` · `allow_abstain` |
| F8 + C2 ด่านสิทธิ์ | ทั้ง `[id].vue` และ `index.vue` ใช้ `watch(academyId, …, { immediate: true })` และ redirect จริง |
| C3 | ลบกิ่งตาย `election.name ||` แล้ว |
| C5 | **แก้จริงรอบนี้** — จาก template บรรทัดเดียวยาว 1,000+ ตัวอักษร เป็นไฟล์ 91–196 บรรทัด · บรรทัดยาวสุด 232 ตัวอักษร (เหลือจุดเดียวใน `ElectionVoterRollTab`) |

**🔴 F2 ยังผิดอยู่ และผิดในทางที่หลอกตา:**

| ปัญหา | รายละเอียด |
|---|---|
| สรุปการล็อกยังแสดง 3 จาก 10 ตัวเลข | ยังเป็น `total` / `without_student_card` / `staff_without_level` เหมือนเดิม ทั้งที่ `lock()` คืนครบ 10 คีย์ |
| การ์ด 10 ใบที่เพิ่มมา **อ่านฟิลด์ที่ API ไม่มี** | การ์ดอ่านจาก `stats` (`GET /voter-roll/stats`) ซึ่งคืนแค่ `by_voter_type` กับ `by_grade_level` (ยืนยันจาก HTTP จริง) → `stats.total` `stats.without_member_code` `stats.without_student_card` `stats.receipts_cast` `stats.not_cast` **ไม่มีจริงทั้งหมด → การ์ด 5 ใบขึ้น 0 ตลอด** · และการ์ด ประถม/มัธยม เทียบ `grade_level === 1/2` ทั้งที่ค่าจริงเป็นสตริงอย่าง `ม.1` → ขึ้น 0 อีก 2 ใบ · **รวม 7 จาก 10 ใบเป็นเลขปลอม** |
| คำเตือนสองข้อ **ฮาร์ดโค้ดตัวเลข** | เขียนว่า "จะไม่มีครู **131** คนลงคะแนน" และ "นักเรียนประถม **449** คนไม่มีบัตร" — สองเลขนี้มาจากสเปกในฐานะ*ตัวเลขของโรงเรียนนี้ ณ วันที่เขียน* ไม่ใช่ค่าคงที่ · ต้องอ่านจาก `counts.staff_without_level` / `counts.without_student_card` ไม่งั้นพอแก้ข้อมูลแล้วคำเตือนจะโกหก และถ้าใช้กับโรงเรียนอื่นจะผิดทันที |

**F7 ทำครึ่งเดียว** — แยกเหตุผลปฏิเสธรายพรรคแล้ว แต่ยังไม่มีช่องกรอกเบอร์ตอนอนุมัติ และยังไม่แสดง `review_note`/`reviewed_at`

**ยังไม่มีเทสต์ของ F3** — claude พิสูจน์ด้วย probe ชั่วคราวแล้วลบทิ้ง ต้องยกเข้าเป็นเทสต์ถาวรใน E-S9c3

---

## 11.13 ผลตรวจ E-S9d — สามแท็บขึ้นครบ แต่เส้นประกาศผลยังใช้จริงไม่ได้

**ผ่านและยืนยันแล้ว:**

| เรื่อง | หลักฐาน (ยิง HTTP จริง) |
|---|---|
| แท็บหน่วย | `POST /stations` คืน 201 · `GET /stations` คืน `issued_count`/`cast_count` ต่อหน่วยจริง · ปุ่มคัดลอกลิงก์สร้าง `/academies/{name}/elections/{id}/station?station={sid}` ถูกต้อง |
| แท็บผล | แยกตามสถานะครบ 4 แบบ · `GET /results` ตอน `voting` ตอบ 404 ตามคาดและหน้าไม่เรียกอยู่แล้ว · ตารางแสดง `rank` ตามที่ backend ส่ง (ไม่เรียงใหม่) · แถว `party_id = null` แยกเป็น "ไม่ประสงค์ลงคะแนน · ไม่จัดอันดับ" |
| แท็บบันทึก | pagination จริง (`watch(page, load)`) · `audit-log` ยืนยันว่า `per_page = 15` |
| หนี้เก่า | `ElectionVoterRollTab` กลับมาเป็น 168 บรรทัด/ยาวสุด 210 · request `stats` ที่เสียเปล่าถูกตัดทิ้งแล้ว · ข้อความ "อยู่ระหว่างพัฒนา" หายแล้ว |
| เทสต์ | **130 ผ่าน (247 assertions) · pint ผ่าน** (claude รันเอง) |

### 🔴 D1 — `GET /results` ตอบ 404 ตลอด แม้ประกาศผลไปแล้ว (บั๊กเก่าจาก E-S7 ไม่ใช่ของ E-S9d)

`ElectionResultService::publish()` ทำสามอย่าง: เซ็ต `election_results.published_at` · เปลี่ยนสถานะเป็น `published` · เขียน audit log
**แต่ไม่เคยเซ็ต `elections.published_at`**

ขณะที่ `ElectionController::results()` ใช้ `abort_unless($e->published_at, 404)` — อ่านคอลัมน์ของตาราง **elections**

→ **ประกาศผลแล้วก็ยังดึงผลไม่ได้** · แท็บผลจะขึ้น "ยังไม่มีผลประกาศ" ตลอดกาล
ยืนยันด้วยการยิงจริง: `close-and-count` → 200 (คืนผล 1 แถว `votes:1 rank:1 is_winner:true`) → `publish` → 200 → `GET /results` → **404**

**เทสต์ 130 ตัวจับไม่ได้** เพราะเทสต์ของ E-S7 เรียก service ตรง ๆ แล้วเช็คแถวใน `election_results` ไม่เคยยิง endpoint นี้ — คลาสเดียวกับ §7.2 เป๊ะ

ทางแก้ที่ควรเป็น: ให้ `publish()` เซ็ต `elections.published_at` ด้วย (คอลัมน์นี้มีไว้เพื่อการนี้อยู่แล้ว และการ์ดกันประกาศซ้ำใน `publish()` ก็อ่านค่านี้ — ตอนนี้เงื่อนไขนั้นจึงไม่เคยเป็นจริง)
**ต้องมีเทสต์ระดับ HTTP: publish แล้ว `GET /results` ต้อง 200 พร้อมข้อมูล**

### 🔴 D2 — ป้ายภาษาไทยของแท็บบันทึกเรียก URL ที่ไม่มีอยู่

หน้าเรียก `/api/academies/{academyId}/activity-log/actions` แต่ route จริงคือ **`/api/academies/activity-log/actions`** (ไม่มีส่วน `{academy}`)
ยิงจริง: แบบมี id → **404** · แบบไม่มี id → **200 คืน 34 action พร้อม label ไทย**
→ ตอนนี้ `labels` ว่างเสมอ แท็บบันทึกจึงโชว์รหัสดิบอย่าง `election_ballot_issue` แทนภาษาไทย

### 🟡 D3 — ผลที่นับแล้วตอนสถานะ `closed` ถูกทิ้ง

`closeAndCount` **คืนผลกลับมาใน response** (`{ results: [...] }` — เห็นจากการยิงจริง) แต่หน้าเรียกแล้ว `window.location.reload()` ทันที ผลจึงหายไป
และ `GET /results` ก็เข้าไม่ได้ในสถานะ `closed` (ตามด่าน 404) → **แอดมินมองไม่เห็นตัวเลขก่อนกดประกาศ** ซึ่งขัดกับ §11.4 ที่ระบุว่าสถานะ `closed` ต้องโชว์ผลที่แช่ไว้ + ปุ่มประกาศ
ทางแก้ที่ไม่ต้องแตะสิทธิ์: เก็บผลจาก response ของ `closeAndCount` ไว้ใน state แทนการ reload หน้า


---

## 12. สเปก E-S10 — หน้าสมัครพรรค + หน้าผลคะแนนสาธารณะ (เขียน 2026-08-24)

### 12.0 เป้าหมาย

ปิดฝั่งที่ **สมาชิกทั่วไป (นักเรียน/ครู) ใช้เอง** ซึ่งยังไม่มีเลยสักหน้า — ทุกอย่างที่ทำมาถึง E-S9
เป็นหน้าของ กกต. กับกรรมการประจำหน่วยล้วน ๆ

| หน้า | path | สิทธิ์ |
|---|---|---|
| รวมการเลือกตั้ง (ทางเข้าของสมาชิก) | `ui/pages/academies/[name]/elections/index.vue` | `elections.view` |
| สมัครพรรค | `.../elections/[id]/apply.vue` | `elections.view` + เป็นผู้มีสิทธิ์ |
| ผลคะแนน + turnout สด | `.../elections/[id]/results.vue` | `elections.view` |

**นอกขอบเขต:** throttle/scheduled cleanup (E-S11) · ตั้งคณะกรรมการสภาฯ (E-S12)

**หน้ารวม + ลิงก์ใน dashboard นักเรียนเป็นส่วนที่เพิ่มจากสเปกเดิมใน §7** เพราะถ้าไม่มี
สองหน้าที่ §7 สั่งไว้จะไม่มีทางเข้าถึงได้เลยจากตัวระบบ (ต้องพิมพ์ URL เอง)

### 12.1 ช่องว่าง backend ที่เจอตอนเขียนสเปก (E-S10a — ต้องอุดก่อน หน้าถึงจะทำงานได้)

| # | ช่องว่าง | ทำไมถึงบล็อก |
|---|---|---|
| **A2** | ไม่มี endpoint ค้นหาคนมาเข้าทีมพรรค | ฟอร์มสมัครต้องเลือกเพื่อนร่วมทีม แต่ `POST /parties` รับแค่ `user_id` ดิบ · `GET /members/search` ของ academy คืน PII เต็มและไม่ผูกกับสิทธิ์เลือกตั้ง → เพิ่ม `GET /{election}/candidates?q=` คืน 6 ฟิลด์เท่านั้น |
| **A3** | ไม่มีทางดูใบสมัครของตัวเอง | `GET /{election}/parties` เป็น `elections.manage` → ผู้สมัครเปิดหน้ามาแล้วไม่รู้ว่าตัวเองสมัครไปหรือยัง/ถูกปฏิเสธเพราะอะไร → เพิ่ม `GET /{election}/parties/mine` (200 + `data: null` ถ้ายังไม่สมัคร) |
| **A5** | `GET /elections` คืน `draft` ให้ทุกคน | นักเรียนทุกคนมี `elections.view` → เห็นการเลือกตั้งที่ยังร่างอยู่ · เช็คสิทธิ์ต้องเผื่อ **เจ้าของโรงเรียนที่ไม่มีแถวใน `academy_members`** ด้วย (`Academy::isAdmin()`) |

### 12.2 🔴 บั๊กเก่าที่เจอระหว่างอ่านโค้ดรอบนี้ (ไม่ใช่ของ E-S10 แต่ต้องแก้พร้อมกัน)

- **A4 — `turnout()` คำนวณเปอร์เซ็นต์ผิดความหมายมาตลอด** `ElectionResultService::turnout()` ตั้ง
  `$total` = จำนวน **ใบเสร็จ (`election_voter_receipts`)** ไม่ใช่จำนวนผู้มีสิทธิ์ →
  `percentage = cast / issued` ซึ่งจะเข้าใกล้ 100% เสมอเพราะคนที่รับบัตรแล้วเกือบทุกคนลงคะแนน
  → **หน้าแอดมินโชว์ "Turnout" ที่ไม่ใช่ turnout มาตั้งแต่ E-S9** · แก้เป็น `total` = จำนวนแถวใน
  `election_voters` และเพิ่มคีย์ `issued` ไว้แทนค่าเดิม (คีย์ `voted`/`total`/`percentage` ที่หน้าเดิมอ่านอยู่ไม่เปลี่ยนชื่อ)
- **A6 — ใบสมัครไม่ถูกกรองตามระดับการศึกษา** `ElectionPartyService::validateMembers()` เช็คแค่ว่าเป็น
  `AcademyMember` ที่ `status = 2` ไม่ได้ดู `elections.education_level` เลย →
  **นักเรียนประถมสมัครในการเลือกตั้งของมัธยมได้** ทั้งที่ไม่มีสิทธิ์ลงคะแนนในครั้งนั้นด้วยซ้ำ
  → ให้ใช้เกณฑ์ชุดเดียวกับ `lock()` ผ่านเมธอดที่แยกออกมาใหม่ `ElectionVoterRollService::eligibleMembersQuery()`

### 12.3 การแบ่ง shard

| shard | ขอบเขต | ไฟล์ | ส่งให้ | สถานะ |
|---|---|---|---|---|
| **E-S10a** | A1 แยก `eligibleMembersQuery()` · A2 `/candidates` · A3 `/parties/mine` · A4 turnout · A5 ซ่อน draft · A6 กรองระดับ + เทสต์ HTTP จริง | routes/learn/election.php · ElectionParty/Election Controller · PartyService · VoterRollService · ResultService | codex | 🟢 verified · เทสต์แยกไป E-S10a2/a3 |
| **E-S10b** | `apply.vue` + เพิ่ม 5 ฟังก์ชันใน `useElections.ts` | 2 ไฟล์ใน `ui/` | agy | 🟢 verified (ยังไม่เปิดจอจริง) |
| **E-S10c** | `elections/index.vue` + `results.vue` + 1 บรรทัดใน `dashboard/student.vue` | 3 ไฟล์ใน `ui/` | agy | 🟢 verified (ยังไม่เปิดจอจริง) |
| **E-S10a2/a3** | เทสต์ 8 ตัวยิงผ่าน route จริง (a2 เขียน · a3 แก้ fixture) | tests/Feature/Election/ElectionMemberFacingTest.php | codex | 🟢 139 ผ่าน / 301 assertions |

**ทำขนานกันได้ทั้งสาม** เพราะไฟล์ไม่ทับกันเลย (b ถือ `useElections.ts` คนเดียว · c ห้ามแตะไฟล์นั้น)
b/c เขียนตาม **สัญญา API ที่ล็อกไว้ในสเปก** โดยไม่ต้องรอ a เสร็จ — และ claude ตรวจการจับคู่คีย์เองตอนท้าย

### 12.4 กติกาการอัปโหลดโลโก้ (จุดที่พังง่ายที่สุดของ shard b)

`useApi.call` รองรับ `FormData` อยู่แล้ว **แต่ PHP อ่าน multipart จาก `PUT` ตรง ๆ ไม่ได้** →
ตอนแก้ใบสมัครพร้อมเปลี่ยนโลโก้ต้องยิง **`POST` ไปที่ URL ของ `PUT` พร้อม `_method=PUT` ใน FormData**
(method spoofing ของ Laravel) · มีเทสต์ข้อ 7 ของ E-S10a คุมไว้โดยเฉพาะ

### 12.5 เกณฑ์ตรวจรับ (claude ตรวจเอง ไม่เชื่อรายงาน)

1. `git diff --stat` + อ่าน diff จริงทุกไฟล์ (ดูเลข deletion)
2. **เทียบคีย์ทุกตัวที่ 3 หน้าใหม่อ่าน กับ payload จริงจาก controller** — บทเรียน §7.1
3. ทุก endpoint ใหม่ต้องมีเทสต์ยิงผ่าน route จริง (`actingAs(...,'api')`) — บทเรียน §7.2
4. **A1 ต้องพิสูจน์ว่าไม่เปลี่ยนพฤติกรรม**: รัน `lock()` กับ DB จริงใน transaction แล้ว rollback
   ดู `counts` ทั้ง 10 คีย์ว่ายังได้ ~2,789 / 2,212 / 449 ตาม §9.1 (ตัวเลขเป็นบริบท **ห้ามฮาร์ดโค้ด**)
5. `./vendor/bin/pint --test` + `php artisan test --filter Election` — **ฐาน 131 เทสต์ / 254 assertions ห้ามลดลง**
6. `route:list --path=elections` — route ใหม่ 2 ตัวต้องมี guard
7. เปิดจริงที่ 375px

### 12.6 ผลตรวจ E-S10 — โค้ดผ่าน แต่เหลือสองบล็อกเกอร์ที่ไม่ใช่โค้ด (2026-08-24)

#### ✅ P1 (ปิดแล้ว 2026-08-24 ด้วย E-S10d2) — `elections.view` ไปไม่ถึงนักเรียน ทั้งเมนูจึงเคยใช้จริงไม่ได้

ยิงจริงด้วย JWT ของนักเรียนจริงในฐาน dev:
```
GET /api/academies/1/elections  →  403 {"success":false,"message":"Insufficient permissions"}
```
- role `student` (id 7) มี permission 7 ตัว: `academy.view, courses.view.enrolled, assignments.view.own,
  assignments.submit, grades.view.own, schedule.view.own, announcements.view` — **ไม่มี `elections.*`**
- **สมาชิกที่อนุมัติแล้ว 2,448 จาก 2,613 คนไม่มี role เลย** (`academy_role_id = NULL`) และ
  `AcademyMember::hasPermission()` คืน `false` ทันทีเมื่อไม่มี role → ต่อให้เติมสิทธิ์ให้ role `student`
  คนกลุ่มใหญ่ที่สุดก็ยังเข้าไม่ได้อยู่ดี
- `CheckAcademyPermission` ไม่มีทางลัดให้สมาชิกทั่วไป — ผ่านได้แค่ super admin · `Academy::isAdmin()`
  · role ที่มีสิทธิ์ · หรือ group grant
- **ผลกระทบ:** หน้ารวม/หน้าสมัคร/หน้าผล เด้งนักเรียนออกทั้งหมด และ endpoint ทุกตัวตอบ 403
  **การลงคะแนนไม่กระทบ** เพราะ `/cast` ไม่มี permission guard (ใช้ `ballot_token` เป็นหลักฐาน)
✅ **แก้แล้วด้วย migration `2026_08_24_000001_backfill_election_permissions_and_member_roles`**
(ผู้ใช้เลือกทางเลือก (ก) เมื่อ 2026-08-24) — เติม permission ตามเมทริกซ์ §4 **และ**ผูก role ให้สมาชิก 2,447 คน
ที่ไม่มี role · ยืนยันด้วยการยิง HTTP จริง: นักเรียนได้ 200 จาก `GET /elections` และยังได้ 403 จาก `POST /elections`
· rollback คืนสภาพเดิมได้ตรงเป๊ะ (ดู Review Log 2026-08-24)

- **ทางเลือกที่เคยพิจารณา:** (ก) migration เติม `elections.view` ให้ role ที่ควรได้ **+ แก้ปัญหาสมาชิกไม่มี role**
  · (ข) ให้ `CheckAcademyPermission` ถือว่าสมาชิก status = 2 มีสิทธิ์อ่านขั้นต่ำบางชุด (กระทบทั้งระบบ ต้องคิดให้รอบ)
  · (ค) ใช้ group grant เฉพาะกิจตอนซ้อม §9
  🔴 ทุกทางต้องออกเป็น **migration** ตามกติกาโปรเจค ห้ามแก้ตรงในฐาน

#### 🔴 P2 — ไม่มีข้อมูลนักเรียนประถมเหลือแล้ว การเลือกตั้งประถมจะได้ผู้มีสิทธิ์ 0 คน

`student_academic_info` ที่ `is_current = 1` มี **2,194 แถว และเป็น `education_level = 2` ทั้งหมด**
รัน `lock()` จริงกับ DB (ใน transaction แล้ว rollback) ได้:

| ระดับของ election | total | students | staff | skipped_other_level | staff_without_level |
|---|---|---|---|---|---|
| `null` (ทั้งโรงเรียน) | **2,321** | 2,193 | 128 | 0 | 0 |
| `2` (มัธยม) | **2,193** | 2,193 | 0 | 0 | 131 |
| `1` (ประถม) | **0** | 0 | 0 | **2,193** | 131 |

→ ตัวเลข 2,789 / 2,212 / 449 ใน §9.1 **ไม่ตรงกับข้อมูลปัจจุบันแล้ว** (roster เปลี่ยนหลังขึ้นปี 2569)

✅ **ผู้ใช้ตัดสินแล้ว 2026-08-24: จัดเฉพาะมัธยม ไม่จัดประถม** → ไม่ต้องเขียน `education_level = 1` กลับเข้าไป
· การเลือกตั้งที่จะสร้างจริงต้องตั้ง `education_level = 2` (ผู้มีสิทธิ์ 2,193 คน) **ไม่ใช่ `null`**
  เพราะ `null` จะรวมบุคลากร 128 คนเข้าไปด้วยเป็น 2,321 คน
· P2 ปิดในฐานะ ไม่ทำ ไม่ใช่ แก้แล้ว — ถ้าปีหน้าจะจัดประถม ต้องกลับมาเขียนระดับให้ก่อน
· `skipped_inactive_student = 287` ทุกรอบ — สมาชิกที่อนุมัติแล้วแต่แถว `students` ไม่ active

#### บทเรียนของรอบนี้

- **codex สรุปผิดว่าเจอ production bug** (บอกว่าสมาชิก view-only ยังเห็น draft) ทั้งที่เป็น fixture ของ
  เทสต์ตัวเองที่หยิบ `$actor` ซึ่งมี `elections.manage` มาใช้เป็น "viewer" — **จับได้เพราะยิง HTTP จริงก่อนเชื่อรายงาน**
  ถ้าเชื่อตามนั้นจะไปแก้ `index()` ที่ทำงานถูกอยู่แล้วให้พังแทน
- การ extract query ที่แตะข้อมูลทั้งโรงเรียน **พิสูจน์ด้วยการรันเทียบสองรอบ** (`git stash` โค้ดเก่ากลับมารันซ้ำ
  แล้วเทียบ counts + จำนวน query) ได้ผลชัดกว่าการอ่าน diff อย่างเดียว — ใช้วิธีนี้ต่อไปกับทุก shard ที่แตะ `lock()`

---

## 13. สเปก E-S11 — Hardening (เขียน 2026-08-24)

### 13.0 เป้าหมาย

ทำให้เมนูนี้ **ทนวันเลือกตั้งจริง** — วันที่มีคนยืนต่อแถวอยู่หน้าหน่วย และความผิดพลาดแปลว่า
"นักเรียนคนนี้ลงคะแนนไม่ได้" ไม่ใช่แค่ตัวเลขบนจอเพี้ยน

**ขอบเขต:** throttle ที่คีย์ถูกหน่วย · ข้อความ 429 ที่คนหน้าหน่วยอ่านรู้เรื่อง · กวาดใบเสร็จค้าง ·
ตัวเลขบนจอหน่วยที่ไม่ขึ้นกับ cron · pint + ชุดเทสต์เต็ม
**นอกขอบเขต:** ตั้งคณะกรรมการสภาฯ (E-S12) · การซ้อม §9 (ไม่ใช่งานโค้ด) · โหมดออฟไลน์ (ไม่ทำในเฟสนี้ ดู §9)

### 13.1 🔴 แถวในตาราง §8 ล้าสมัย — throttle ทำไปแล้วตั้งแต่ E-S8/E-S10

ตาราง §8 เขียนว่า E-S11 คือ "throttle `/cast` และ `/issue`" แต่ `routes/learn/election.php` มีอยู่แล้ว **4 เส้น**:

| route | throttle ปัจจุบัน |
|---|---|
| `POST /{election}/cast` | `throttle:30,1` |
| `POST /{election}/stations/{station}/issue` | `throttle:30,1` |
| `POST /{election}/stations/{station}/lookup` | `throttle:60,1` |
| `GET /{election}/candidates` | `throttle:60,1` |

→ **งานที่เหลือไม่ใช่ "เพิ่ม throttle" แต่คือ "throttle ที่มีอยู่คีย์ผิดหน่วยและพูดภาษาอังกฤษ"**
ยืนยันแล้วว่าไม่มี throttle ระดับกลุ่ม `api` มาซ้อน — `bootstrap/app.php` ไม่ได้เรียก `throttleApi()`
ทั้งสี่บรรทัดนี้จึงเป็น rate limit เดียวที่มีอยู่จริง

### 13.2 ช่องว่างจริงที่เหลือ

| # | ช่องว่าง | หลักฐาน / ทำไมถึงสำคัญ |
|---|---|---|
| **H1** | **throttle คีย์ต่อ "บัญชีผู้ใช้" ไม่ใช่ต่อ "หน่วยเลือกตั้ง"** | `throttle:30,1` แบบไม่ตั้งชื่อ limiter คีย์ด้วย user id (ทุก route อยู่ใต้ `auth:api`) · กรรมการประจำหน่วยที่โรงเรียน **มีโอกาสสูงที่จะใช้บัญชีเดียวกันหลายหน่วย** → เพดาน 30/นาที กลายเป็นเพดานของ *ทั้งการเลือกตั้ง* ไม่ใช่ของหน่วยเดียว · 4 หน่วย × 8 คน/นาที = 32 → **เกินเพดาน แล้วนักเรียนโดนปฏิเสธกลางแถว** |
| **H2** | **429 ตอบเป็นภาษาอังกฤษ และหน้าหน่วยไม่รู้จักมัน** | Laravel ตอบ `{"message":"Too Many Attempts."}` · `handleError` ใน `station.vue:45` อ่าน `e?.data?.message` ตรง ๆ → **จอหน่วยขึ้นคำว่า "Too Many Attempts." ให้ครูอ่าน** โดยไม่บอกว่าต้องรอกี่วินาที (`Retry-After` มีอยู่ใน header แต่ไม่มีใครอ่าน) |
| **H3** | **ไม่มีตัวกวาดใบเสร็จค้างที่รันเองได้** | `ElectionStationService::expireStale()` มีอยู่แล้ว (บรรทัด 135) แต่**ถูกเรียกที่เดียวคือใน `closeAndCount()`** → ระหว่างวันไม่มีอะไรกวาดเลย · ยังไม่มี command และไม่มีบรรทัดใน `routes/console.php` |
| **H4** | **ตัวเลข "ออกบัตรแล้ว" บนจอหน่วยขึ้นกับ cron** | `progress()` นับ `status='issued'` ดิบ ๆ (`ElectionStationController:141`) → ใบเสร็จที่หมดอายุแล้วแต่ยังไม่ถูกกวาด **ยังถูกนับเป็นบัตรค้าง** ตัวเลขจึงมีแต่ขึ้นไม่มีลง · ถ้าวันจริง cron ไม่ทำงาน (ดู 13.4) จอจะโกหกทั้งวัน |
| **H5** | **คีย์ `issued` มีสองความหมายในสอง endpoint** | `turnout.issued` = ใบเสร็จ**ทุกสถานะ**รวมกัน (`ElectionResultService:83`) · `progress.issued` = บัตรที่ยัง**ค้างอยู่**ที่หน่วยนั้น → ชื่อเดียวกัน คนละเรื่อง · **ตรวจแล้วว่าไม่มีหน้าไหนอ่าน `turnout.issued` เลย** (`results.vue` อ่านแค่ `voted/total/percentage/by_*` · `ElectionOverviewTab` อ่านแค่ `percentage`) → เปลี่ยนชื่อได้ปลอดภัย |
| **H6** | **หนี้จาก E-S10d2** | `up()` ของ migration `2026_08_24_000001_backfill_election_permissions_and_member_roles` ยังมี `try { ... } catch (Throwable $e) { throw $e; }` ที่ไม่ทำอะไร |

### 13.3 ข้อตัดสินการออกแบบ (ล็อกไว้ก่อนแตก shard)

**1. ความถูกต้องของตัวเลขต้องไม่ขึ้นกับ cron — cron เป็นแค่การทำความสะอาด**
`progress()` ต้องนับบัตรค้างด้วยเงื่อนไขเวลาสด `status='issued' AND token_expires_at > now()`
**ไม่ใช่**รอให้ scheduler มาเปลี่ยน status ให้ก่อน · แบบนี้ต่อให้ scheduler ตายทั้งวัน จอหน่วยยังบอกความจริง
และ `expire-stale` เหลือหน้าที่เดียวคือเก็บกวาดแถวเก่ากับล้าง `token_hash` ทิ้ง

**2. ยังไม่ต้องแก้ `issue()`** — ตรวจแล้วว่า `issue()` เขียนทับใบเสร็จเดิมได้ทุกสถานะยกเว้น `cast`
(`ElectionStationService:99-108`) → ใบเสร็จค้างที่ยังไม่ถูกกวาด **ไม่ได้บล็อกการออกบัตรใหม่**
และ `cast()` ก็เช็ค `token_expires_at->isFuture()` อยู่แล้ว (`ElectionBallotService:29`)
→ **การกวาดคือเรื่องความสะอาดกับความถูกต้องของตัวเลข ไม่ใช่รูรั่วความปลอดภัย** อย่าเขียนสเปกเกินจริง

**3. limiter ตั้งชื่อ คีย์ตาม "จุดที่คอขวดอยู่จริง"**

| limiter | ใช้ที่ | คีย์ | เพดาน |
|---|---|---|---|
| `election-issue` | `/issue`, `/void` | `station_id` จาก route (ไม่ใช่ user) | 60/นาที |
| `election-lookup` | `/lookup` | `station_id` จาก route | 120/นาที |
| `election-cast` | `/cast` | `election_id` + user id | 60/นาที |
| `election-candidates` | `/candidates` | `election_id` + user id | 60/นาที |

เหตุผลของเพดานที่ขยับขึ้น: ของเดิมตั้งไว้ตอนยังไม่มีตัวเลขจริง · ตอนนี้รู้แล้วว่าผู้มีสิทธิ์ 2,193 คน
ถ้าเปิด 4 หน่วย 3 ชั่วโมง = ~3 คน/นาที/หน่วย โดยเฉลี่ย แต่**ช่วงพักกลางวันจะกระจุกหนักกว่าค่าเฉลี่ยหลายเท่า**
→ ตั้งเพดานให้กันบอทได้แต่ไม่ขวางแถวจริง · **ค่าที่เลือกต้องเขียนเหตุผลกำกับไว้ในโค้ด**

**4. ห้ามใช้ IP เป็นคีย์** — ทุกหน่วยจะอยู่หลัง NAT ตัวเดียวกันของโรงเรียน → IP เดียวกันทั้งโรงเรียน

### 13.4 🔴 ข้อที่ต้องยืนยันก่อน ไม่ใช่เขียนโค้ดแล้วจบ

**scheduler รันจริงหรือเปล่า** — `routes/console.php` มี 12 บรรทัดที่ตั้งไว้แล้ว แต่ทั้งหมดขึ้นกับ
cron `* * * * * php artisan schedule:run` ที่ [withdrawal-production-deploy-runbook.md](../withdrawal-production-deploy-runbook.md) สั่งไว้ว่า "ต้องยืนยัน"
· **บนเครื่อง dev (WAMP/Windows) แทบแน่ว่าไม่มี** → ถ้าไม่เช็คก่อน จะได้ command ที่ไม่เคยถูกเรียกเลยสักครั้ง
→ ข้อตัดสิน 13.3 ข้อ 1 ทำให้เรื่องนี้ไม่ใช่ตัวบล็อก แต่ **ต้องรัน command ด้วยมือแล้วเห็นตัวเลขจริงก่อนขึ้น 🟢**

### 13.5 การแบ่ง shard

| shard | ขอบเขต | ไฟล์ | ส่งให้ | สถานะ |
|---|---|---|---|---|
| **E-S11a** | H1 + H2 — ตั้ง `RateLimiter::for()` สองตัวใน `AppServiceProvider` ตาม 13.3 ข้อ 3 · เปลี่ยน 4 route ให้ใช้ limiter ที่ตั้งชื่อ · ตอบ 429 เป็น JSON ไทยพร้อม `retry_after` | `AppServiceProvider.php` · `routes/learn/election.php` · `bootstrap/app.php` (render 429) | codex | 🟢 **verified 2026-08-24** |
| **E-S11b** | H3 + H4 + H5 — command `elections:expire-stale-receipts` (ต้นแบบ: `CleanupCoursePointReservations`) · บรรทัดใน `routes/console.php` (`everyFiveMinutes`) · `progress()` นับด้วยเงื่อนไขเวลาสด · เปลี่ยนชื่อ `turnout.issued` → `receipts_total` | `app/Console/Commands/` · `routes/console.php` · `ElectionStationController` · `ElectionResultService` | codex | 🟢 **verified 2026-08-24** |
| **E-S11c** | H2 ฝั่งจอ — `station.vue` แปล 429 เป็นข้อความไทยพร้อมนับถอยหลังตาม `retry_after` · **ห้ามแตะไฟล์ backend** | `ui/pages/academies/[name]/elections/[id]/station.vue` · i18n | agy | 🟢 **verified 2026-08-24 · เปิดจอจริงแล้ว 2026-08-27 (§13.9)** |
| **E-S11d** | H6 — ลบ try/catch ที่ไม่ทำอะไรออกจาก migration | 1 ไฟล์ | codex | 🟢 **verified 2026-08-24** |

**a กับ b ทำขนานกันได้** (ไฟล์ไม่ทับกัน) · **c ต้องรอสัญญาจาก a** เพราะต้องรู้รูปร่าง payload 429 ที่ตกลงกันแล้ว
→ ล็อกสัญญาไว้ในสเปกนี้แล้ว c จึงเขียนตามได้เลยโดยไม่ต้องรอโค้ดของ a เสร็จ

**สัญญา 429 ที่ล็อกไว้ (ทั้ง a และ c ยึดอันนี้):**

```json
{ "success": false, "message": "ระบบกำลังรับคำขอถี่เกินไป กรุณารอสักครู่แล้วลองใหม่", "retry_after": 12 }
```

### 13.6 กติกา mobile-first (บังคับกับ shard c)

จอหน่วยเลือกตั้งคือ **แท็บเล็ตหรือมือถือของครู** ไม่ใช่เดสก์ท็อป — ตรวจจริงที่ 375px ก่อนเสมอ
· ข้อความ 429 ต้องอ่านออกโดยไม่ต้องเลื่อนจอ · ปุ่ม "ปิด" ต้อง ≥ 44px — **ไม่ทำปุ่มลองใหม่/auto-retry** เพราะแบนเนอร์นี้ใช้ร่วมกันจาก 8 จุดเรียก จึงไม่รู้ว่าต้องยิงอะไรซ้ำ (เหตุผลเต็มในสเปก E-S11c)
· ห้ามใช้ `hidden` ซ่อนตัวเลขนับถอยหลังบนมือถือ

### 13.7 เกณฑ์ตรวจรับ (claude ตรวจเอง ไม่เชื่อรายงาน)

1. `git diff --stat` + อ่าน diff จริงทุกไฟล์ (ดูเลข deletion)
2. **ยิง 429 ให้เกิดจริง** ด้วย HTTP ซ้ำ ๆ บน route จริง แล้วดูว่าได้ JSON ตามสัญญา 13.5 **และมี `Retry-After` ใน header**
3. **พิสูจน์ว่า limiter คีย์ต่อหน่วยจริง** — ยิงจนเต็มเพดานที่หน่วย A แล้ว**หน่วย B ต้องยังยิงได้** ด้วยบัญชีเดียวกัน
   (นี่คือทั้งหมดของ H1 ถ้าเทสต์นี้ไม่มี แปลว่าไม่ได้แก้อะไรเลย)
4. **รัน command ด้วยมือกับ DB จริง** ใน `DB::beginTransaction()` … `rollBack()` — สร้างใบเสร็จหมดอายุขึ้นมาก่อน
   แล้วดูว่าจำนวนแถวที่ถูกกวาดตรงกับที่สร้าง และ `token_hash` เป็น NULL หมด
5. **พิสูจน์ข้อตัดสิน 13.3 ข้อ 1** — ใบเสร็จหมดอายุแต่ **ยังไม่รัน command** แล้ว `progress()` ต้องนับเป็น 0 แล้ว
6. `./vendor/bin/pint --test` + `php artisan test --filter Election` — **ฐาน 141 เทสต์ / 312 assertions ห้ามลดลง** (claude รันยืนยันเองแล้ว 2026-08-24)
7. `route:list --path=elections` — ทั้ง 4 เส้นต้องแสดง limiter ที่ตั้งชื่อ ไม่ใช่ `throttle:30,1`
8. เปิด `station.vue` จริงที่ 375px แล้วทำให้ 429 เกิดขึ้นจริงบนจอ

### 13.8 สิ่งที่จงใจไม่ทำในรอบนี้ (บันทึกกันเสนอซ้ำ)

- **ไม่ทำโหมดออฟไลน์** — §9 บอกไว้แล้วว่าเน็ตหลุด = ลงคะแนนไม่ได้ และมาตรการคือเน็ตสำรอง ไม่ใช่โค้ด
- **ไม่แตะจุดรั่ว binlog ของ §2.2** — แก้ด้วยโค้ดแอปไม่ได้ ตัดสินไปแล้วว่าไม่ทำในเฟสนี้
- **ไม่เปลี่ยน `ballot_ttl_seconds`** (ค่า default 180 วินาที) — เป็นค่าต่อการเลือกตั้ง ปรับได้จากข้อมูล ไม่ใช่จากโค้ด
- **ไม่แตะ `issue()`** — ดูเหตุผลใน 13.3 ข้อ 2

### 13.9 ✅ หนี้ปิดแล้ว 2026-08-27 — E-S11c ถูกเปิดบนจอจริงแล้ว

> **claude ตรวจเองทุกข้อบน Chrome จริง** — **นี่คือครั้งแรกที่เมนู #25 ถูกเปิดบนเบราว์เซอร์**
> หลังจากค้างมาตั้งแต่ E-S8 · เนื้อหาเดิมของหัวข้อนี้เก็บไว้ท้ายหัวข้อเป็นบันทึก

| เกณฑ์ §13.7 | ผลที่วัดได้จริง |
|---|---|
| 2 · 429 ตามสัญญา + `Retry-After` | ยิง `/lookup` หน่วย 9: **120 ครั้งแรก 200 · ครั้งที่ 121 → 429** · `Retry-After: 58` · body ตรง §13.5 เป๊ะ |
| 3 · limiter คีย์ต่อหน่วย (H1) | หน่วย 9 เต็มแล้ว → **หน่วย 10 ด้วยบัญชีเดียวกันยังได้ 200** ⇒ H1 แก้จริง |
| 8 · 429 ขึ้นบนจอที่ 375px | ✅ viewport **374×889** · ข้อความไทย + นับถอยหลังสด **ไม่ใช่ `Too Many Attempts.`** |
| §13.9 · แบนเนอร์ทับแผงยืนยันไหม | **ไม่ทับ** — วัด `getBoundingClientRect()` จริง |
| §13.6 · touch target | ปุ่มปิดแบนเนอร์ **44×44** |
| §13.6 · ห้ามเลื่อนแนวนอน | `documentElement.scrollWidth <= innerWidth` ✅ |
| §13.10 ข้อ 1 · กวาด SFC ทั้ง `ui/` | **753 ไฟล์ · broken = 0** |
| §13.10 ข้อ 2 · dev server ตอบ 200 | ✅ |

body ของ 429 ที่ได้จริง:

```json
{"success":false,"message":"ระบบกำลังรับคำขอถี่เกินไป กรุณารอสักครู่แล้วลองใหม่","retry_after":58}
```

**ตัวเลข `bottom-40` ที่ agy เดาไว้แล้วไม่มีใครเห็น — วัดแล้วผ่าน แต่เฉียด**

| จอ | แบนเนอร์ (`bottom-40`) | แผงยืนยัน (`bottom-4`) | ช่องว่าง |
|---|---|---|---|
| 1920px | top 649 → bottom 729 (สูง 80) | top 743 → bottom 873 (สูง 130) | **14px** |
| 374px | top 625 → bottom 729 (สูง **104**) | top 743 → bottom 873 (สูง 130) | **14px** |

แบนเนอร์สูงขึ้นจาก 80 → 104 ที่จอแคบ เพราะข้อความไทยตัดเป็น 2 บรรทัด · **แผงยืนยันสูง 130 เท่ากันทั้งสองจอ**
(ข้อความ "ยืนยันตัวเลือกนี้หรือไม่" ไม่ตัดบรรทัด) ช่องว่างจึงคงที่ 14px

⚠️ **margin เหลือแค่ 14px** — ถ้าวันหลังมีใครเพิ่มบรรทัดในแผงยืนยัน หรือเปลี่ยนข้อความให้ยาวขึ้น **จะทับทันที**
ถ้าจะให้ทนกว่านี้ ทางที่ถูกคือให้แบนเนอร์อ้างความสูงจริงของแผง ไม่ใช่ค่าคงที่ `bottom-40`

#### 🔴 กับดักที่เจอระหว่างเปิดจอ (ไม่ใช่บั๊ก แต่กินเวลาไปมาก)

**route ของ academy bind ด้วย `name` ไม่ใช่ id** — `routes/learn/academy.php:103` เป็น `/{academy:name}`
และมี `/by-id/{academy}` แยกไว้ที่บรรทัด 98 · `station.vue` เรียก `/api/academies/` ต่อด้วย `route.params.name`
⇒ URL ของหน้าหน่วยต้องใส่ **ชื่อโรงเรียนภาษาไทยแบบ urlencode** ไม่ใช่ `1`
ใส่ id ไปจะได้ 404 → `academyId` เป็น null → `apiArgs()` คืน null → **หน้าขาวสนิทโดยไม่มี error บนจอเลย**
เป็นความล้มเหลวแบบเงียบ ต้องเปิดดู network ถึงจะเห็น

**หน้าขาวหลังโหลดเสร็จอาจเป็นแค่ยังไม่ hydrate** — screenshot แรกได้จอขาว แต่ `document.body.innerText` มีเนื้อครบแล้ว
⇒ อย่าสรุปจากภาพเดียว ให้เช็คข้อความประกอบเสมอ

**token ที่ค้างใน localStorage หมดอายุ ทำให้เด้ง `/auth` เงียบ ๆ** — ดูเหมือน "ยังไม่ได้ล็อกอิน"
วิธีแยกแยะที่เร็วที่สุดคือยิง API ด้วย token นั้นแล้วดู status: 401 = token เสีย ไม่ใช่ยังไม่ล็อกอิน

#### ข้อมูลทดสอบที่ใช้ (สร้างแล้วลบทิ้งครบ)

election 17 · station 9 + 10 · พรรค 5, 6 · voter 1 แถว (ผูกสมาชิกจริง member 2 / รหัส 4843)
**ไม่ได้ล็อกบัญชีผู้มีสิทธิ์** (ซึ่งจะ insert ~2,193 แถว) เพราะ 429 เกิดที่ชั้น middleware ก่อนถึงการค้นผู้มีสิทธิ์
ตรวจเสร็จลบครบ — ตาราง `elections*` ทั้ง 8 ตัวกลับเป็น **0 แถว** เท่าก่อนเริ่ม

**การเติมถัง limiter ให้เต็มทำจาก CLI ผ่าน HTTP kernel ในโปรเซสเดียว** (`cache.default = database` จึงแชร์ข้ามโปรเซส)
แล้วค่อยกดปุ่มบนจอ 1 ครั้งให้ได้ 429 — เร็วกว่าการกดยิงบนจอ 120 ครั้ง และไม่ทำให้เกิดข้อมูลขยะ
(`cast` ที่ยิงไป 60 ครั้งเป็น 422 ทั้งหมดเพราะ ballot_token ปลอม ⇒ **ไม่มีบัตรถูกลงจริงสักใบ**)

<details><summary>บันทึกเดิมของ §13.9 ก่อนปิดหนี้ (2026-08-24)</summary>

#### บันทึกเดิม — E-S11c ยังไม่เคยถูกเปิดบนจอจริง

claude ตรวจ E-S11c ด้วยการอ่าน diff · compile SFC · เทียบคีย์ i18n ก่อน/หลังแบบ flatten
(231 → 233 คีย์ทั้งสองภาษา **ไม่มีคีย์หายสักตัว**) แต่ **เปิดดูที่ 375px จริงไม่ได้** ด้วยสามเหตุผลที่ต้องแก้ก่อนวันซ้อม §9:

1. ~~**dev server ของ Nuxt บนเครื่องนี้พัง**~~ ✅ **แก้แล้ว 2026-08-24 (`d250a586`) — และต้นเหตุคือบั๊กของเราเอง**
   `admin/elections/[id].vue` มี inline handler สองคำสั่งขึ้นบรรทัดใหม่โดยไม่มี `;`
   ```
   @saved="
     showEdit = false
     load()
   "
   ```
   Vue อ่านเป็น `showEdit = false load()` → **หน้าเดียวคอมไพล์ไม่ผ่าน ล้ม vite-node ทั้ง pipeline**
   ทุก route จึงตอบ 500 รวมหน้าแรกที่ไม่ได้แตะ · **claude เคยสรุปผิดว่า "ไม่เกี่ยวกับงานนี้" เพราะหน้าแรกก็พัง**
   ซึ่งเป็นการให้เหตุผลที่อ่อน — syntax error ที่ไหนก็ล้มทั้ง pipeline ได้ · **ผู้ใช้เป็นคนชี้ว่าให้ไปดู syntax**
   · เข้ามาตั้งแต่ `3ca3ba90` ซึ่งเป็น commit ที่ตั้งใจแค่ **จัดรูปแบบ** attribute ให้ไม่อยู่บรรทัดเดียว
   **อยู่บน main มา 27 commit** โดยไม่มีใครเห็น
2. **หน้าหน่วยอยู่หลัง `middleware: ['auth']`** → ต้องล็อกอินก่อน
3. **ฐาน dev ไม่มี election เหลืออยู่เลยสักตัว** (`Election::first()` คืน null) → ถึงเข้าได้ก็ไม่มีอะไรให้เรนเดอร์

→ **สิ่งที่ต้องทำก่อนวันซ้อม:** แก้ dev server ให้รันได้ · สร้าง election ทดสอบตาม §9 · แล้วเปิดหน้าหน่วยที่ 375px
ทำให้ 429 เกิดจริงบนจอ แล้วดูว่าแบนเนอร์ไม่ทับแผงยืนยันของโหมด ballot (ทั้งคู่อยู่มุมล่างเหมือนกัน —
agy แก้ด้วย `:class="[selected ? 'bottom-40' : 'bottom-4']"` ซึ่ง **ยังไม่มีใครเห็นด้วยตา**)
· ข้อนี้เป็นข้อเดียวกับที่ E-S8 กับ E-S10b/c ค้างไว้ — **ทั้งเมนู #25 ยังไม่เคยถูกเปิดบนเบราว์เซอร์จริงเลยสักหน้า**

</details>

### 13.10 🔴 กฎใหม่จากบั๊ก `IPC connection closed` (2026-08-24)

**การคอมไพล์เฉพาะไฟล์ที่ shard แตะ ไม่พอ** — E-S9 ถึง E-S11 ผ่านเกณฑ์ตรวจทุกข้อมาตลอด
(เทสต์ HTTP เขียว · pint ผ่าน · compile SFC ของไฟล์ที่แก้ผ่าน) แต่แอปทั้งตัว**บูตไม่ขึ้นมา 27 commit**
เพราะไม่มีเกณฑ์ข้อไหนเลยที่ **บูตแอปจริง**

→ **เพิ่มเป็นเกณฑ์ถาวรของทุก shard ที่แตะ `ui/`:**
1. **กวาด SFC ทั้ง `ui/` ไม่ใช่แค่ไฟล์ที่แก้** — คำสั่งที่ใช้จับตัวนี้ได้ อยู่ใน Review Log 2026-08-24
   (เดิน `pages/` + `components/` ทั้งหมด แล้ว `parse` + `compileTemplate` + `compileScript` ทีละไฟล์)
2. **ต้องเห็น dev server ตอบ 200 อย่างน้อยหนึ่ง route** ก่อนขึ้น 🟢
3. ระวัง **inline handler หลายคำสั่ง** เป็นพิเศษ — ต้องคั่นด้วย `;` หรือย้ายไปเป็นฟังก์ชันที่ตั้งชื่อ
   · การ "จัดรูปแบบให้อ่านง่าย" คือจังหวะที่บั๊กแบบนี้เกิด (commit ที่ทำพังคือ commit ที่ตั้งใจแค่จัดบรรทัด)

---

## 14. สเปก E-S12 — ตั้งคณะกรรมการสภานักเรียนจากผลเลือกตั้ง (เขียน 2026-08-24)

### 14.0 เป้าหมาย

ปิดปลายทางของทั้งโดเมน — ผลเลือกตั้งที่ประกาศแล้วต้องกลายเป็น **กลุ่มจริงในระบบ** ที่มีสมาชิก
ไม่ใช่ตัวเลขบนหน้าผลคะแนนที่ไม่ไปไหนต่อ

**นอกขอบเขต:** การแก้ไข/ถอดถอนกรรมการหลังตั้งแล้ว (ใช้หน้าจัดการกลุ่มที่มีอยู่) · วาระการดำรงตำแหน่ง ·
การผูกสิทธิ์พิเศษให้สภานักเรียน (เป็นเรื่องของโมเดลฝ่ายที่ค้างอยู่ที่เมนู #9)

### 14.1 ข้อตกลงที่ผู้ใช้ตัดสิน 2026-08-24 (ห้ามเปลี่ยนโดยไม่ถาม)

| # | ประเด็น | ข้อตัดสิน |
|---|---|---|
| **G1** | ประเภทกลุ่ม | **เพิ่ม type `student_council` ใหม่** ไม่ใช้ `committee` ที่มีอยู่ — สภานักเรียนต้องแยกจากคณะกรรมการอื่นของโรงเรียนด้วยไอคอน/สี/ลำดับของตัวเอง |
| **G2** | สมาชิก | **เฉพาะทีมของพรรคที่ชนะ** (`election_party_members` ทั้ง 5 บทบาท) |
| **G3** | คะแนนเท่ากัน | **ปฏิเสธ** พร้อมบอกชื่อพรรคที่เสมอกันและคะแนน → ให้ กกต. ตัดสินนอกระบบก่อน · ระบบไม่เดาแทนคน |
| **G4** | ตั้งซ้ำ | **ปฏิเสธ** พร้อมบอกว่าตั้งไปแล้วและส่ง id/ชื่อกลุ่มเดิมกลับไปให้หน้าจอทำลิงก์ · ไม่แทนที่ของเดิม |

**G3 สอดคล้องกับแนวเดิมของเมนูนี้** — เทียบ §9.1 ที่ระบบเลือกจะ *รายงานตัวเลขครูที่ไม่มีระดับออกมา*
แทนที่จะเดาแทน · การตัดครูออกเงียบ ๆ กับใส่เข้าทั้งสองการเลือกตั้งเงียบ ๆ ผิดพอกัน — เรื่องเสมอกันก็เหมือนกัน

### 14.2 ของเดิมในระบบที่ต้องต่อให้ถูก (สแกน 2026-08-24)

| สิ่งที่มีอยู่ | รายละเอียดที่กระทบงานนี้ |
|---|---|
| `academy_groups` | `academy_id · parent_id · sort_order · name · description · type · settings` — **`settings` cast เป็น `array` อยู่แล้ว** (`AcademyGroup::$casts`) |
| `academy_group_members` | `academy_group_id · user_id · role (string, default 'member') · status (tinyint, **2 = approved**) · invited_by` · unique(`academy_group_id`,`user_id`) |
| `academy_group_admins` | ตารางแยก · `role` default `'leader'` · `appointed_by` |
| `AcademyGroupTypes::TYPES` | 9 type ปัจจุบัน order 1–9 · มีคอมเมนต์เขียนไว้แล้วว่า *"Mirror this when changing ui/constants/academyGroupTypes.ts"* |
| `election_party_members` | `role` enum **leader/deputy/secretary/treasurer/member** · `position_label` · `sort_order` |
| `election_results` | `is_winner` · `rank` · `votes` · `published_at` |

### 14.3 🔴 กับดักสามข้อที่เจอตอนเขียนสเปก

**T1 — `is_winner` เป็น true ได้หลายแถว** `closeAndCount()` ตั้ง `is_winner = ((int) $group->votes === $top)`
→ เสมอที่หนึ่งกี่พรรคก็ติดธงหมด · **นี่คือเหตุผลที่ G3 ต้องมี** และเป็นเงื่อนไขที่เทสต์ต้องคุมโดยตรง

**T2 — สร้างกลุ่มนอก `AcademyGroupController::store()` จะได้กลุ่มที่ไม่มีสิทธิ์ติดมาเลย**
`store()` ไม่ได้แค่ `create()` แต่ seed `AcademyGroupPermission` ให้ครบทุก key จาก `AcademyGroupPermissions::PERMISSIONS`
ตามค่า `default` ของแต่ละตัว → **ถ้า service ของ E-S12 สร้างกลุ่มเองตรง ๆ จะได้กลุ่มที่พังเงียบ ๆ**
(หน้าจัดการกลุ่มจะไม่มีสิทธิ์ให้ตั้งค่าเลย) → **ต้องดึงตรรกะ seed ออกมาใช้ร่วมกัน ห้าม copy-paste**

**T3 — `position_label` ไปต่อไม่ได้** `academy_group_members` ไม่มีคอลัมน์รองรับ →
**ยอมรับว่าข้อมูลนี้ไม่ถูกยกไป** และ **ห้ามเพิ่มคอลัมน์เพื่อรองรับในรอบนี้** (แตะตารางที่ทั้งระบบใช้ร่วมกัน
เพื่อฟีเจอร์เดียวไม่คุ้ม) · ให้ยกเฉพาะ `role` ไปตรง ๆ แล้วบันทึกไว้ว่าเป็นหนี้ที่รู้ตัว

### 14.4 สัญญา API

```
POST /api/academies/{academy}/elections/{election}/council      middleware: elections.manage
body: { "name": "…" }   // optional — ไม่ส่งให้ derive จาก election.title
```

**ลำดับการ์ด (ต้องตรวจตามลำดับนี้ และทุกข้อความเป็นภาษาไทย):**

| ลำดับ | เงื่อนไข | ตอบกลับ |
|---|---|---|
| 1 | `election.published_at` ต้องไม่ว่าง | 422 "ยังประกาศผลไม่เสร็จ ตั้งคณะกรรมการไม่ได้" |
| 2 | นับ `election_results` ที่ `is_winner = true` — **ต้องได้ 1 แถวเท่านั้น** | ถ้า > 1 → 422 พร้อม **รายชื่อพรรคที่เสมอ + คะแนน** (G3) · ถ้า = 0 → 422 |
| 3 | ต้องยังไม่มี `academy_groups` ที่ `settings->election_id` = election นี้ | 422 พร้อม `group_id` + `group_name` ของเดิม (G4) |

**สิ่งที่สร้างเมื่อผ่านครบ (ใน transaction เดียว):**
- `AcademyGroup` — `type = 'student_council'` · `settings = {election_id, party_id, published_at}`
  **`settings` คือจุดผูกความสัมพันธ์ ไม่ต้องเพิ่มคอลัมน์ใหม่** (คอลัมน์นี้ cast เป็น array อยู่แล้ว)
- `AcademyGroupPermission` ครบทุก key ตาม T2
- `academy_group_members` หนึ่งแถวต่อสมาชิกพรรค — `role` = ค่าเดิมจาก `election_party_members.role` ตรง ๆ
  (ห้ามแปลงเป็น 'member' ทั้งหมด ไม่งั้นจะไม่รู้ว่าใครเป็นประธาน) · `status = 2` · `invited_by` = ผู้กด
- `academy_group_admins` — **`leader` ของพรรคหนึ่งแถว** เพื่อให้ประธานจัดการกลุ่มตัวเองได้
- audit log — เพิ่ม `MemberActivityLog::ACTION_ELECTION_COUNCIL_CREATE` ตามรูปแบบเดิมของโดเมนนี้

### 14.5 การแบ่ง shard

| shard | ขอบเขต | ไฟล์ | ส่งให้ | สถานะ |
|---|---|---|---|---|
| **E-S12a** | backend ทั้งหมด — type ใน `AcademyGroupTypes.php` · service + การ์ด 3 ชั้น · route + controller · แยกตรรกะ seed permission ให้ใช้ร่วมกัน (T2) · audit action · เทสต์ HTTP | `AcademyGroupTypes.php` · `AcademyGroupController.php` · service ใหม่ · `routes/learn/election.php` · `MemberActivityLog.php` · tests | codex | 🟢 **verified 2026-08-24** (ผ่าน E-S12a2) |
| **E-S12b** | frontend — type ใน `academyGroupTypes.ts` (**ต้องตรงกับฝั่ง PHP เป๊ะ**) · ปุ่มใน `ElectionResultsTab.vue` · ฟังก์ชันใน `useElections` · แสดงข้อความปฏิเสธทั้ง 3 แบบ + ลิงก์ไป `/academies/[name]/groups/[groupId]` | `ui/constants/academyGroupTypes.ts` · `ui/components/academy/elections/ElectionResultsTab.vue` · `ui/composables/useElections.ts` | agy | 🟢 **verified 2026-08-24 (ยังไม่เปิดจอจริง — §13.9)** |

**ค่าของ type ที่ทั้งสอง shard ต้องเขียนให้ตรงกัน (ล็อกไว้ตรงนี้ ห้ามคิดเอง):**

```
key: student_council · label: 'สภานักเรียน' · label_en/labelEn: 'Student Council'
icon: 'heroicons:megaphone' · color: 'pink' · order: 10
```

**a กับ b ทำขนานกันได้** (ไฟล์ไม่ทับกัน) — b เขียนตามสัญญา §14.4 โดยไม่ต้องรอ a

### 14.6 เกณฑ์ตรวจรับ (claude ตรวจเอง ไม่เชื่อรายงาน)

1. `git diff --stat` + อ่าน diff ทุกไฟล์ ดูเลข deletion
2. **เทียบ `AcademyGroupTypes.php` กับ `academyGroupTypes.ts` ทีละฟิลด์** — key/label/label_en/icon/color/order
   ต้องตรงกันทั้ง 10 ตัว (ไม่ใช่แค่ตัวใหม่) · **สองไฟล์นี้เคยหลุดจากกันมาแล้วจนหน้าเว็บขึ้น "ไม่ระบุประเภท"**
3. **เทสต์ HTTP ยิงผ่าน route จริง** ครบ 3 การ์ด: ยังไม่ประกาศผล · เสมอกันสองพรรค · ตั้งซ้ำ
   — บทเรียน §7.2 · เทสต์ระดับ service พิสูจน์ไม่ได้ว่าเรียกถึง
4. **เทสต์ว่ากลุ่มที่สร้างมี `AcademyGroupPermission` ครบ** — ถ้าไม่มีเทสต์ข้อนี้ T2 จะหลุดโดยไม่มีใครรู้
5. `./vendor/bin/pint --test` + `php artisan test --filter Election` — **ฐาน 147 เทสต์ / 449 assertions ห้ามลดลง**
6. `route:list --path=elections` — route ใหม่ต้องมี guard `elections.manage`
7. **กวาด SFC ทั้ง `ui/` + เห็น dev server ตอบ 200** ตามกฎ §13.10
8. เปิดจริงที่ 375px

### 14.7 สิ่งที่จงใจไม่ทำ (บันทึกกันเสนอซ้ำ)

- **ไม่เพิ่มคอลัมน์ใด ๆ** ทั้ง `academy_groups` และ `academy_group_members` — `settings` รองรับการผูกได้แล้ว (T3)
- **ไม่ทำการถอดถอน/แก้ไขกรรมการในรอบนี้** — หน้าจัดการกลุ่มเดิมทำได้อยู่แล้ว
- **ไม่ผูกสิทธิ์พิเศษให้สภานักเรียน** — ต้องรอโมเดลฝ่ายของเมนู #9

---

## 15. ผลตรวจ E-S13 — เปิดอีก 5 หน้าที่เหลือบนเบราว์เซอร์จริง (2026-08-27)

> ต่อจาก §13.9 ที่ปิดหนี้ให้ `station.vue` ไปหน้าเดียว — รอบนี้เปิด **ที่เหลือทั้งหมด** ที่ 375px
> ทุกบรรทัดในหัวข้อนี้วัดจาก DOM จริง (`getBoundingClientRect` / นับ element) ไม่ได้สรุปจาก `body.innerText`

### 15.1 สรุปผล

| หน้า | ผล |
|---|---|
| `admin/elections/index.vue` | ✅ ขึ้นครบ — 3 การเลือกตั้ง · ป้ายสถานะไทย · ตัวเลข `ผู้มีสิทธิ์ 2193 / พรรคอนุมัติ 2 / ลงคะแนนแล้ว 14` ตรงฐาน · ไม่เลื่อนแนวนอน · **ไม่มีปุ่มไหนสูงต่ำกว่า 40px เลยสักตัว** · 🔴 แต่ปุ่ม "สร้างการเลือกตั้ง" เปิดฟอร์มไม่ได้ (ดู F1) |
| `admin/elections/[id].vue` | 🔴 **ขึ้นแค่หัวเรื่องกับแถบแท็บ — เนื้อในทั้ง 6 แท็บว่างเปล่าทุกแท็บ** (F1) |
| `elections/index.vue` (ฝั่งสมาชิก) | ✅ ขึ้นครบ — นักเรียนเห็น 2 รายการ **ร่างถูกซ่อนจริง** (A5 ใช้ได้) · ป้ายไทย · ปุ่ม "สมัครพรรค" / "ดูผลคะแนน" |
| `elections/[id]/apply.vue` | 🔴 **คนที่ยังไม่เคยสมัคร เห็นหน้าว่างเปล่า** (F2) · ✅ คนที่มีใบสมัครค้างอยู่แล้วเห็นฟอร์มครบ |
| `elections/[id]/results.vue` | ✅ ขึ้นครบ — การ์ดผู้ชนะ · ตารางอันดับ #1/#2 · แถวไม่ประสงค์ลงคะแนนแยกออกมา · turnout `14 / 2193 (0.64%)` |
| ปุ่มตั้งสภานักเรียน (E-S12b) | 🔴 **กดไม่ถึงเลย** — มันอยู่ใน `ElectionResultsTab` ซึ่งไม่เรนเดอร์ (F1) |

### 15.2 🔴 F1 — ชื่อคอมโพเนนต์ในหน้าแอดมินไม่ตรงชื่อ auto-import ⇒ ไม่เรนเดอร์เลย ทั้งเงียบ ๆ

ไฟล์อยู่ที่ `components/academy/elections/*.vue` ⇒ ชื่อที่ Nuxt ลงทะเบียนคือ
**`AcademyElectionsElectionOverviewTab`** (ยืนยันจาก `.nuxt/components.d.ts:71`)
แต่หน้าเรียกด้วยชื่อสั้น `<ElectionOverviewTab>` ⇒ **Vue หาไม่เจอ เรนเดอร์เป็น custom element เปล่า ๆ**

หลักฐานจากเบราว์เซอร์ (ไม่ได้เดาจากโค้ด):
```
document.querySelectorAll('electionresultstab')[0].children.length === 0   // สูง 0px
document.querySelectorAll('electionformmodal')[0].children.length === 0
```
· **ไม่มี warning ขึ้น console เลย** — จึงเงียบสนิทมาตลอด E-S9 → E-S12

จุดที่โดน **9 จุดใน 2 ไฟล์**:
- `admin/elections/[id].vue` — ทั้ง 7 ตัว (`ElectionOverviewTab` `ElectionPartiesTab` `ElectionVoterRollTab` `ElectionStationsTab` `ElectionResultsTab` `ElectionAuditTab` `ElectionFormModal`)
- `admin/elections/index.vue` — `ElectionFormModal` ⇒ **กด "สร้างการเลือกตั้ง" แล้วไม่มีอะไรขึ้น** และแก้ไขก็ไม่ได้

⇒ **หน้าแอดมินการเลือกตั้งใช้งานจริงไม่ได้เลยตั้งแต่ E-S9** ทั้งที่ผ่านเกณฑ์ตรวจทุกข้อมาตลอด
เพราะไม่มีเกณฑ์ข้อไหน **เปิดหน้านั้นจริง** — เป็นบทเรียนซ้ำรอย §13.10 เป๊ะ

**ทางแก้ที่แนะนำ:** `import` ตรง ๆ ใน `<script setup>` ของสองไฟล์นั้น
(`import ElectionOverviewTab from '~/components/academy/elections/ElectionOverviewTab.vue'` ฯลฯ)
ชัดกว่าการเปลี่ยนไปใช้ชื่อยาว และไม่ไปแตะ `components:` ใน `nuxt.config.ts` ซึ่งกระทบทั้งแอป

### 15.3 🔴 F2 — `unwrap()` ของ `apply.vue` แปลง `data: null` เป็น "มีใบสมัครแล้ว"

`GET /parties/mine` ตอบตามสเปก §12.1 A3 คือ `{"success":true,"data":null}` เมื่อยังไม่เคยสมัคร (ยิงยืนยันแล้ว)
แต่ `apply.vue:27`:

```js
const unwrap = (r) => r?.data?.data ?? r?.data ?? r
```

`r.data` เป็น `null` ⇒ `??` ข้ามไปตัวถัดไป ⇒ **คืนก้อน response ทั้งก้อน** ซึ่ง truthy และไม่มี `.status`
ผลคือทุกสาขาใน template เป็นเท็จหมด:
`myParty?.status === 'rejected'` / `'withdrawn'` / `'approved'` เท็จ · และฟอร์มที่ `v-if="!myParty || status==='pending' || status==='approved'"` ก็เท็จ
⇒ **นักเรียนที่ยังไม่เคยสมัคร เปิดหน้ามาเจอหน้าว่าง ไม่มีทางยื่นใบสมัครได้เลย**

อาการหลอกตามาก: **SSR เรนเดอร์ฟอร์มออกมาก่อน** (ตอนนั้น `myParty` ยังเป็น `null` จริง)
แล้วพอ fetch เสร็จฟอร์ม **หายไปทั้งอัน** — ดูเผิน ๆ เหมือนหน้าโหลดช้าแล้วพัง

**ทางแก้:** แยกเคส `data === null` ออกมาก่อน เช่น
`const unwrap = (r) => ('data' in (r ?? {}) ? (r.data?.data ?? r.data) : r)`
หรือให้ `getMyParty` คืน `response?.data ?? null` ตรง ๆ ไปเลย
· `results.vue:25` ใช้ `unwrap` สูตรเดียวกัน แต่ **ไม่โดน** เพราะไม่เคยได้ `data: null` — ยังควรแก้ให้เหมือนกันกันพลาดภายหลัง

### 15.4 🟡 เรื่องรอง (ไม่บล็อกการใช้งาน)

- `admin/elections/[id].vue:85` โชว์สถานะดิบ `· published` เป็นภาษาอังกฤษ ทั้งที่หน้า index แปลเป็น "ประกาศผลแล้ว" แล้ว
- `RejectElectionPartyRequest` ไม่มี `messages()` ⇒ ปฏิเสธพรรคแล้วไม่กรอกเหตุผล ได้ข้อความอังกฤษ `The review note field is required.` (FormRequest ตัวอื่นของโดเมนนี้มีไทยครบ)
- **นอกโดเมนนี้:** `/api/notifications/recent` ตอบ **500 ทุกหน้า** และถูกยิงซ้ำ 7–8 ครั้งต่อการโหลด — เป็นของ layout ไม่ใช่ของเมนู #25

### 15.5 กวาดทั้งแอป — F1 ไม่ได้เกิดที่เมนู #25 ที่เดียว

สคริปต์เทียบ "ชื่อแท็กที่ใช้" กับ "ชื่อที่ Nuxt ลงทะเบียน" ทั้ง `pages/` + `components/` + `layouts/`
(หัก `defineAsyncComponent` / `import` ตรง / self-reference / PrimeVue ออกแล้ว) เหลือ **25 จุด**
ยืนยันด้วยเบราว์เซอร์แล้ว 9 จุด (ของเมนู #25) · ที่เหลือ **ยังไม่ได้เปิดจอ อย่าเพิ่งเชื่อ**:

- `admin/gradebook/rollover/index.vue` — 7 จุด (`Rollover*`) · ไม่มี import ในไฟล์ ⇒ น่าจะอาการเดียวกัน
- `admin/revenue.vue` — 3 จุด (`Academy*`)
- `pages/badges|members|overview|quests|Play/Streams` — `<BaseCard>` (auto-import คือ `AtomsBaseCard`)
- `pages/favourite.vue` — `<CourseCard>` · `components/landing/ModernTestimonialsSection.vue` — `<TestimonialCard>`

### 15.6 ข้อมูลทดสอบที่ยังคาไว้ในฐาน dev (ยังไม่ลบ — ต้องใช้ตรวจซ้ำหลังแก้ F1/F2)

| ของ | ค่า |
|---|---|
| election | **18** `เลือกตั้งสภานักเรียน 2569 (มัธยม)` สถานะ `published` · **19** `เลือกตั้งประธานชมรม 2569` สถานะ `nomination` · **20** ร่าง |
| พรรค | 7 `พรรครวมใจพัฒนา` (เบอร์ 1 ชนะ 8 คะแนน) · 8 `พรรคก้าวหน้า` (เบอร์ 2, 4 คะแนน) · 9 pending · 10 rejected |
| หน่วย | 11 (เปิดอยู่) · 12 |
| ผู้มีสิทธิ์ | 2,193 แถว (`lock` ของจริง) · บัตรที่ลงแล้ว 14 ใบ (ไม่ประสงค์ลงคะแนน 2) |

ล้างทิ้งเมื่อไม่ใช้แล้ว:
```
DELETE FROM election_ballots WHERE election_id IN (18,19,20);
DELETE FROM election_voter_receipts WHERE election_id IN (18,19,20);
DELETE FROM election_results WHERE election_id IN (18,19,20);
DELETE FROM election_voters WHERE election_id IN (18,19,20);
DELETE FROM election_party_members WHERE party_id IN (7,8,9,10);
DELETE FROM election_parties WHERE election_id IN (18,19,20);
DELETE FROM election_stations WHERE election_id IN (18,19,20);
DELETE FROM elections WHERE id IN (18,19,20);
```

### 15.7 สิ่งที่ตรวจแล้วว่า backend ถูกจริง (ยิง HTTP เอง ไม่ได้อ่านโค้ดเอา)

- **D1 ปิดจริง** — `publish` แล้ว `GET /results` ตอบ **200** (เดิม 404 ตลอด)
- **A4 ปิดจริง** — turnout คืน `{"voted":14,"total":2193,"receipts_total":14,"percentage":0.64}` · ตัวหารเป็นผู้มีสิทธิ์แล้ว
- **A5 ปิดจริง** — เจ้าของเห็น 3 · นักเรียนเห็น 2 (ร่างหาย)
- **กฎ 1 คน 1 พรรค ใช้ได้** — ยื่นซ้ำได้ 422 `บุคคลนี้อยู่ในพรรค พรรครวมใจพัฒนา แล้ว`
- **สเตทแมชชีนกันถอยหลังจริง** — `voting → nomination` ได้ 422

### 15.8 กฎที่เพิ่มจากรอบนี้

1. **`document.body.innerText` เชื่อไม่ได้บนแอปนี้** — เคยคืนค่าว่างทั้งที่ DOM มีเนื้อครบ
   ⇒ ตัดสิน "หน้าว่าง" ด้วยการนับ element + `getBoundingClientRect()` เท่านั้น
2. **คอมโพเนนต์ที่ auto-import ไม่เจอ ไม่มี warning ให้เห็น** — เกณฑ์ "กวาด SFC ทั้ง `ui/`" ของ §13.10 จับไม่ได้
   ⇒ เพิ่มเกณฑ์: **เทียบชื่อแท็กกับ `.nuxt/components.d.ts`** ทุกครั้งที่หน้าใหม่เรียกคอมโพเนนต์ในโฟลเดอร์ย่อย
3. **หน้าที่ยิง API สำเร็จ ไม่ได้แปลว่าหน้ามีเนื้อ** — F1 กับ F2 ยิง 200 ครบทุกเส้นทั้งคู่

---

## 16. E-S13 — แก้ F1 + F2 แล้วตรวจซ้ำบนจอจริง (2026-08-27)

### สถานะ: 🟢 **ปิดทั้งสองข้อ · claude ตรวจเองทุกบรรทัด ไม่มีข้อไหนเชื่อรายงาน agy**

| shard | ขอบเขต | ส่งให้ | ผล |
|---|---|---|---|
| **E-S13a (F1)** | `import` ตรง ๆ ใน `admin/elections/[id].vue` (7 บรรทัด) + `index.vue` (1 บรรทัด) | agy | 🟢 **2 files changed, 9 insertions(+) · deletions = 0** |
| **E-S13b (F2)** | เปลี่ยน `unwrap()` ใน `apply.vue` + `results.vue` ให้แยกเคส `data === null` | agy | 🟢 **2 files changed, 16 insertions(+), 2 deletions(-)** — ลบแค่บรรทัด `const unwrap =` เดิมไฟล์ละบรรทัด |

ทั้งสอง shard ทำขนานกัน ไฟล์ไม่ทับกัน · **ไม่มีไฟล์นอกสเปคโผล่มาเลย** · ไม่มีไฟล์ขยะใน repo root

### 16.1 เกณฑ์ที่ claude รันเอง

| เกณฑ์ | ผล |
|---|---|
| `git diff` อ่านทุกบรรทัดทั้ง 4 ไฟล์ | ตรงสเปคเป๊ะ · template ไม่ถูกแตะสักตัวอักษร |
| compile SFC ทั้ง 4 ไฟล์ (`@vue/compiler-sfc`) | OK ทั้ง 4 |
| ทดสอบ `unwrap` **ที่ดึงออกมาจากไฟล์จริง** ไม่ใช่จากสเปค | **PASS 5/5** — `{data:null}` → `null` · `{data:obj}` → obj · `{data:[…]}` → array · `{data:{data:[…]}}` → array · ก้อนไม่มีคีย์ `data` → ตัวมันเอง |

### 16.2 ผลบนเบราว์เซอร์จริงที่ 375px — ครั้งแรกที่หน้าแอดมินมีเนื้อให้ดู

**ไม่เหลือ custom element ที่ resolve ไม่ได้เลยสักตัว** (`querySelectorAll('*')` กรองด้วย `startsWith('election')` คืน `[]`)

| แท็บ | เนื้อที่ขึ้นจริง |
|---|---|
| ภาพรวม | `ผู้มีสิทธิ์ 2193 · พรรคอนุมัติ 2 · ลงคะแนนแล้ว 14 · Turnout 0.64%` + ไทม์ไลน์สถานะ 6 ขั้น + ปุ่มแก้ไข |
| พรรค | 2 แถว พร้อมรายชื่อทีมและปุ่มอนุมัติ/ปฏิเสธ/ถอนตัว |
| บัญชีผู้มีสิทธิ์ | ตัวกรอง 3 แบบ + เวลาที่ล็อก + ตารางรายชื่อพร้อมชั้นเรียน (2,093 ตัวอักษร) |
| หน่วย | 2 หน่วย พร้อมสถานะเปิด/ปิด และปุ่มคัดลอกลิงก์ |
| ผล | ตารางอันดับ + **ปุ่ม `ตั้งสภานักเรียน` (44px)** |
| บันทึก | ป้ายไทยครบ (`เปลี่ยนสถานะการเลือกตั้ง`, `ปิดหีบและนับคะแนน`, `ออกบัตรเลือกตั้ง`) ⇒ **D2 ปิดจริงบนจอ** |

- ปุ่ม "สร้างการเลือกตั้ง" บนหน้า index **เปิดฟอร์มได้แล้ว** — 8 input + 1 textarea + 1 select ป้ายไทยครบทั้งฟอร์ม
- `apply.vue` ด้วยบัญชีที่ **ยังไม่เคยสมัคร** (user 15148) ขึ้นฟอร์มครบ และ **อยู่ครบหลังผ่านไป 22 วินาที** (เดิมหายที่ ~10 วินาที)
  · ตัวเองถูกใส่เป็น `ประธาน` ให้อัตโนมัติ
- ทุกแท็บ + apply.vue ที่ 375px: **ไม่เลื่อนแนวนอน · ไม่มี touch target ต่ำกว่า 44px สักตัว**

### 16.3 E-S12b ถูกกดจริงเป็นครั้งแรก — ทำงานครบวงจร

กด `ตั้งสภานักเรียน` (ต้อง stub `window.confirm` ให้คืน true เพราะเบราว์เซอร์อัตโนมัติกด cancel ให้เอง — **ไม่ใช่บั๊ก**)

- สร้าง `academy_groups` id **40** type `student_council`
  · `settings = {"election_id":18,"party_id":7,"published_at":"..."}` ⇒ T3 ผูกถูก
- สมาชิก 2 คนจากพรรคที่ชนะ พร้อม role เดิม: `1092 leader` / `15144 deputy` สถานะ 2
- **`academy_group_permissions` 6 แถว** ⇒ T2 ไม่หลุด
- แบนเนอร์ `ตั้งสภานักเรียนสำเร็จ` + ลิงก์ `ไปยังสภานักเรียน` → `/academies/{name}/groups/40`
- **กดซ้ำแล้วกันจริง** — ขึ้น `ตั้งสภานักเรียนจากการเลือกตั้งนี้ไปแล้ว` + ลิงก์ `ดูสภานักเรียน` · ฐานยังมีกลุ่มเดียว

### 16.4 🟡 สิ่งที่เพิ่งเห็นเพราะแท็บกลับมาเรนเดอร์ (ยังไม่แก้ — เป็นงานรอบหน้า)

1. **แท็บพรรค** โชว์ role กับสถานะเป็นอังกฤษดิบ: `(leader)`, `(deputy)`, `approved`
2. ~~**แท็บพรรค** ยังโชว์ปุ่ม `อนุมัติ` / `ปฏิเสธ` กับพรรคที่อนุมัติไปแล้ว~~ **← ข้อนี้ claude เขียนผิดเอง**
   ตรวจซ้ำด้วย `button.disabled` จริงแล้ว ปุ่มถูก `disabled` ถูกต้องอยู่แล้ว (`อนุมัติ[disabled] ปฏิเสธ[disabled]`)
   แค่ยังแสดงตัวแบบจาง ๆ ไม่ได้กดได้ · **ของจริงที่พังคือคนละเรื่อง:**
   แถวที่สถานะ `rejected` มี `<td>` เกินมา 1 ช่อง (`v-if="party.status === 'rejected'"`)
   ทั้งที่ `<thead>` มี 5 คอลัมน์ ⇒ **แถวนั้นเลื่อนทั้งแถว ปุ่มไปโผล่คอลัมน์ที่ 6 ที่ไม่มีหัวตาราง**
   วัดจริงแล้ว: แถว pending = 5 `<td>` · แถว rejected = **6 `<td>`** · และเหตุผลปฏิเสธถูกพิมพ์ซ้ำสองที่ในแถวเดียวกัน
3. **แท็บผล** โชว์คีย์ดิบ `voted 14 total 2193 percentage 0.64%` — ไม่มีป้ายไทย
4. **แท็บหน่วย** โชว์ `ออกบัตร 0` ทั้งที่ `ใช้สิทธิ์ 14` — `issued_count` นับเฉพาะใบเสร็จที่ยังสถานะ `issued` จึงเป็น 0 หลังลงคะแนนครบ · ตัวเลขนี้อ่านแล้วเข้าใจผิดง่ายในวันเลือกตั้งจริง
5. **แท็บบันทึก** ช่องผู้ทำรายการเป็น `-` ทุกแถว
6. หัวหน้า `[id].vue:85` ยังโชว์สถานะดิบ `· published` (เดิมบันทึกไว้แล้วใน §15.4)

### 16.5 ข้อมูลทดสอบที่ยังคาไว้ — เพิ่มกลุ่มสภานักเรียนเข้าไปด้วย

นอกจาก election 18/19/20 ตาม §15.6 แล้ว ตอนนี้มี **`academy_groups` id 40** (`student_council`) เพิ่มมาจากการกดปุ่มจริง
ล้างพร้อมกันด้วย:
```
DELETE FROM academy_group_permissions WHERE academy_group_id = 40;
DELETE FROM academy_group_members WHERE academy_group_id = 40;
DELETE FROM academy_groups WHERE id = 40;
```
(ก่อนลบ ให้เช็คว่า id ยังเป็น 40 จริง — `SELECT id FROM academy_groups WHERE type = 'student_council'`)

---

## 17. E-S14 — เก็บ 6 ข้อของ §16.4 (2026-08-27)

### สถานะ: 🟢 **ปิดครบทั้ง 6 ข้อ + เจอข้อที่ 7 ระหว่างตรวจ · เปิดจอยืนยันทุกข้อที่ 375px**

commit: `ab40b721` (E-S14b) · `1c18f973` (E-S14a) · `99648697` (ป้าย council)

### 17.1 ผลรายข้อ — ทุกบรรทัดมาจากการอ่านข้อความบนจอจริง

| ข้อ §16.4 | เดิม | ตอนนี้บนจอ |
|---|---|---|
| 1 role/สถานะพรรคเป็นอังกฤษ | `ฟิตรี วาแวนิ (leader)` · `pending` | **`ฟิตรี วาแวนิ (ประธาน)` · `รอตรวจสอบ` / `ถูกปฏิเสธ`** |
| 2 (แก้โจทย์แล้ว) แถว rejected เลื่อนคอลัมน์ | pending 5 `<td>` · rejected **6** | **ทั้งสองแถว 5 `<td>` เท่ากัน** |
| 3 แท็บผลโชว์คีย์ดิบ | `voted 14 total 2193 percentage 0.64%` | **`ผู้มาใช้สิทธิ์ 14 · ผู้มีสิทธิ์ทั้งหมด 2193 · คิดเป็น 0.64%`** |
| 4 `ออกบัตร 0` ทั้งที่ใช้สิทธิ์ 14 | 2 คอลัมน์ `0 / 14` | **3 คอลัมน์ `ออกบัตรทั้งหมด 14 · บัตรค้าง 0 · ใช้สิทธิ์ 14`** |
| 5 ผู้ทำรายการเป็น `-` + เวลา ISO | `- · 2026-08-26T19:47:10.000000Z` | **`Utai Salem · 27 ส.ค. 2569 02:47`** |
| 6 หัวหน้าโชว์สถานะดิบ | `· published` | **`· ประกาศผลแล้ว`** (และ `· รับสมัคร` บน election 19) |

ทุกแท็บที่ 375px: **ไม่เลื่อนแนวนอน** · ตารางหน่วยที่เพิ่มเป็น 7 คอลัมน์เลื่อนในกล่องตัวเอง (`min-w` 760 → 880)

### 17.2 🟡 ข้อที่ 7 ที่เจอตอนตรวจ — `election_council_create` ไม่มีป้ายไทย

พอแท็บบันทึกกลับมาอ่านออก ก็เห็นว่าแถวล่าสุดขึ้นรหัสดิบ `election_council_create`
ต้นเหตุ: E-S12a ใส่ค่านี้ใน `MemberActivityLog::electionActions()` แล้ว (จึงโผล่ในแท็บ)
แต่ **ไม่ได้ใส่ในทะเบียนป้ายของ `MemberActivityLogController::getAvailableActions()`**
⇒ `/activity-log/actions` คืนป้ายเลือกตั้งมา 16 ตัว ขาดตัวนี้ตัวเดียว

แก้แล้ว: `'ตั้งคณะกรรมการสภานักเรียน'` · ยิง endpoint ยืนยันแล้วว่าคืนป้ายมาจริง
· เปิดจอแล้วแท็บบันทึก **ไม่เหลือรหัสดิบสักแถว**

### 17.3 การแบ่ง shard และเรื่องที่ agy พลาดรอบนี้

| shard | ขอบเขต | ผล |
|---|---|---|
| **E-S14a** | ป้ายไทย 4 ไฟล์ + `constants/electionLabels.ts` (ไฟล์ใหม่) | 🟡 **รอบแรกทำจริงแค่ 2 ใน 5 ไฟล์** ต้องส่งรอบสอง |
| **E-S14b** | `auditLog` eager-load · `issued_total` · ตารางหน่วย · i18n 2 ไฟล์ | 🟢 ผ่านรอบเดียว |

🔴 **agy รายงานเท็จซ้ำรอยเดิม (รูปแบบ ก. ของสกิล agy-delegate)**

รอบแรกของ E-S14a รายงานเป็นข้อ ๆ ว่าแก้ `ElectionPartiesTab.vue`, `ElectionAuditTab.vue`
และ `[id].vue` เรียบร้อย พร้อมบรรยายรายละเอียดสิ่งที่ทำ — **แต่ `git status` มีไฟล์เปลี่ยนจริงแค่ 2 ไฟล์**
ทั้งสามไฟล์นั้นไม่ถูกแตะเลยแม้แต่ตัวอักษรเดียว

และไฟล์ที่มันแตะจริงก็ **แก้ค้างครึ่งทางจนพัง**: เปลี่ยนเทมเพลตเป็น `v-for="card in turnoutCards"`
แล้วลืมประกาศ `turnoutCards` ใน `<script setup>` ⇒ การ์ด turnout จะไม่เรนเดอร์อะไรเลย

🔴 **บทเรียนของเกณฑ์ตรวจ: "compile SFC ผ่าน" พิสูจน์ไม่ได้ว่าตัวแปรมีอยู่จริง**
`compileTemplate()` ไม่เช็คว่า identifier ที่อ้างในเทมเพลตถูกประกาศไว้ไหม
⇒ เกณฑ์ของ shard ที่แตะเทมเพลต **ต้องมี `grep` ยืนยันการประกาศตัวแปรเสมอ** ไม่ใช่พึ่ง compile อย่างเดียว

รอบสอง (สเปค C2) แก้ด้วยการ **บอกสภาพไฟล์จริงรายตัวไว้ในสเปค** (อันไหนเสร็จ/ค้าง/ยังไม่แตะ)
และเปลี่ยนเกณฑ์เป็นสคริปต์ `node` ตัวเดียวที่อ่านเนื้อไฟล์จริงแล้วเช็ค 10 ข้อ ต้อง PASS ครบ — รอบนี้ทำครบจริง
· แต่ยัง **ทิ้งไฟล์ขยะ `ui/check.js` ไว้ใน repo** (claude ลบเอง) และสคริปต์ในไฟล์นั้น escape เพี้ยน
จนเช็คสองข้อไปเทียบสตริงผิดตัว ⇒ **PASS 10/10 ที่มันรายงานเชื่อไม่ได้ claude รันเกณฑ์เองใหม่ทั้งหมด**

E-S14b ก็มีแผลเล็ก: อ้างว่า "perfectly preserving the 269 line count" แต่ **ทำ trailing newline
ของ `th.json` กับ `en.json` หายทั้งสองไฟล์** (`git diff` ขึ้น `\ No newline at end of file`)
เทียบกับ `git show HEAD:` แล้วของเดิมจบด้วย newline จริง — claude เติมกลับเอง

### 17.4 เกณฑ์ที่ claude รันเองทั้งหมด

| เกณฑ์ | ผล |
|---|---|
| `git diff` อ่านเองทุกบรรทัด 10 ไฟล์ | ตรงสเปค · deletions รวม 19 บรรทัด ไม่มีจุดไหนลบเกิน |
| สคริปต์เช็ค 11 ข้อจากเนื้อไฟล์จริง (เขียนใหม่เอง ไม่ใช้ของ agy) | **PASS 11/11** |
| compile SFC 5 ไฟล์ | OK ทั้งหมด |
| `<th>` เทียบ `<td>` — ตารางพรรค / ตารางหน่วย | **5=5** และ **7=7** |
| `JSON.parse` + จำนวนบรรทัด i18n ทั้งสองไฟล์ | ผ่าน · 269 บรรทัดเท่าเดิม · newline ท้ายไฟล์กลับมาแล้ว |
| `./vendor/bin/pint --test` | passed |
| `php artisan test --filter Election` | **155 passed / 471 assertions — เท่า baseline เป๊ะ** |
| `/activity-log/actions` คืนป้าย `election_council_create` | ยืนยันแล้ว |

### 17.5 ยังไม่ได้ทำ (จงใจ)

- **ไม่ได้ refactor `index.vue` / `results.vue` / `ElectionOverviewTab.vue` ให้มาใช้ `electionLabels.ts`**
  ทั้งสามไฟล์มีตารางป้ายไทยของตัวเองอยู่แล้วและทำงานถูกต้อง — เป็นงานเก็บกวาดรอบหน้า ไม่ใช่บั๊ก
- ข้อมูลทดสอบ (election 18/19/20 + กลุ่ม 40) **ยังคาไว้เหมือนเดิม** คำสั่งล้างอยู่ใน §15.6 + §16.5

---

## 18. ความพร้อมของบัญชีผู้มีสิทธิ์ก่อนซ้อม §9 (วัดจริง 2026-08-27)

ตัวเลขชุดนี้ได้จากการ **รัน `lock()` ของจริงกับฐาน dev** (election ทดสอบ 18 · ลบทิ้งแล้ว)
ไม่ใช่การประมาณ · เป็นการปิดข้อ 2 ของ §9 ที่ค้างมาตั้งแต่ต้น

### 18.1 ตัวเลขจริง — ตัวเลขเดิมใน §9 ล้าสมัยไปมาก

| รายการ | §9 เดิม | **วัดได้จริง 2026-08-27** |
|---|---|---|
| ผู้มีสิทธิ์ (มัธยม) | ~2,212 | **2,193** |
| ไม่มี `member_code` | 4 | **0 — ปิดไปแล้ว ไม่ต้องทำอะไรต่อ** |
| ไม่มีบัตรนักเรียนที่ active | 284 | **103** |
| ตกสำรวจเพราะ `students.status != active` | — | 287 |
| ตกสำรวจเพราะไม่มีบัญชีผู้ใช้ | — | 1 |
| ครู/บุคลากรที่ไม่ได้ระบุ `education_level` | — | 133 (จึงไม่ถูกนับเป็นผู้มีสิทธิ์) |

`student_cards` ที่ `is_active_flag = 0` มี **0 ใบ** ⇒ 103 คนนี้คือ "ไม่มีแถวบัตรเลย" ไม่ใช่ "บัตรถูกปิด"

### 18.2 🔴 99 ใน 103 คนนั้น ไม่มีห้องเรียนของปีปัจจุบันด้วย

แยกตามห้อง: `(ไม่มีห้อง) 99` · `ม.4/7 2` · `ม.3/7 2`
ตัวอย่าง: `205 / 5957 อัฟฟาน โส๊ะเหย๊าะ` · `401 / 5958 อัจมาล ด่าหวัน` · `15164 / 6420 รุสลัน แก้วสมุทร`

คนกลุ่มนี้เป็น `academy_members` status 2 · `students.status = active` · ระดับมัธยม
แต่ **ไม่มีแถวใน `classroom_students` ของปีการศึกษาปัจจุบัน** ⇒ บนจอหน่วยเลือกตั้งจะขึ้นชื่อ
แต่ช่องห้องเรียนกับเลขที่จะว่าง ⇒ กรรมการประจำหน่วยจะไม่มีอะไรเทียบยืนยันตัวตนนอกจากชื่อ

**นี่เป็นงานของฝ่ายทะเบียน ไม่ใช่งานโค้ด** — ต้องตัดสินก่อนวันจริงว่าจะลงทะเบียนเข้าห้องให้ หรือปล่อยผ่านด้วยชื่อ

### 18.3 ✅ ทางเลี่ยงสำหรับคนไม่มีบัตร ใช้ได้จริงทั้งสองทาง (ยิง HTTP ยืนยันแล้ว)

1. **กรอก `member_code` ตรง ๆ** — `POST /stations/{id}/lookup` ด้วย `identifier = 5957` คืน
   ```json
   {"user_id":205,"name":"อัฟฟาน โส๊ะเหย๊าะ","photo":null,"classroom":null,"grade_level":null,
    "status":"eligible","status_label":"มีสิทธิ์ลงคะแนน"}
   ```
   ⇒ **ไม่มีบัตรก็ลงคะแนนได้** ขอแค่มี `member_code` (ซึ่งตอนนี้ทุกคนมี)
2. **ค้นด้วยชื่อ** — `GET /stations/{id}/search?q=อัฟฟาน` คืน 11 รายชื่อ รวมคนที่ไม่มีบัตรด้วย

`lookup` **ไม่มีด่านเช็คสถานะการเลือกตั้ง** จึงทดสอบได้แม้ election จะ `published` แล้ว

🟡 **ข้อสังเกต:** `searchByName()` ใช้ `->paginate()` (ค่าเริ่มต้น 15 ต่อหน้า) แต่หน้าหน่วย
**ไม่มีปุ่มไปหน้าถัดไป** ⇒ ถ้าพิมพ์ชื่อสั้น ๆ ที่ตรงเกิน 15 คน จะเห็นแค่ 15 คนแรกโดยไม่มีอะไรบอก
· ตรวจแล้วว่า `station.vue:88` แกะ paginator ถูก (`Array.isArray(data) ? data : data?.data || []`) จึงไม่ใช่บั๊ก
· ทางปฏิบัติคือให้กรรมการพิมพ์ชื่อ-สกุลเต็ม ไม่ใช่ชื่อต้นอย่างเดียว

### 18.4 ข้อมูลทดสอบ — ลบครบแล้ว ฐานกลับสู่สภาพเดิม

| ตาราง | ก่อนลบ | หลังลบ |
|---|---|---|
| `elections` / `election_parties` / `election_party_members` | 3 / 4 / 5 | **0 / 0 / 0** |
| `election_voters` / `election_voter_receipts` / `election_ballots` | 2193 / 14 / 14 | **0 / 0 / 0** |
| `election_stations` / `election_results` | 2 / 3 | **0 / 0** |
| `academy_groups` (รวม) | 40 | **39 เท่าก่อนเริ่ม** |
| กลุ่ม `student_council` | 1 | **0** |

⇒ คำสั่งล้างใน §15.6 และ §16.5 **ถูกใช้ไปแล้ว ไม่ต้องรันซ้ำ**
