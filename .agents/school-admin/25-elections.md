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

> **ตัวเลขคาดหมายบนเครื่องนี้:** 3,063 คน = student 2,931 + staff 132 (สมาชิก 3,064 แถว มี status=3 อยู่ 1 แถวที่ต้องคัดออก)
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
| **E-S8** | **หน้าหน่วยเลือกตั้ง (station.vue)** — งาน frontend ที่สำคัญที่สุด ตาม §7 | E-S6 | FE | ⚪ |
| **E-S9** | **หน้าแอดมิน** — index + [id] 6 แท็บ + เมนูใน `admin.vue` | E-S7 | FE | ⚪ |
| **E-S10** | **หน้าสมัครพรรค + หน้าผลคะแนน + turnout realtime** | E-S9 | FE | ⚪ |
| **E-S11** | **Hardening** — throttle `/cast` และ `/issue` · กวาด receipt ค้างเป็น `expired` (scheduled) · pint + ชุดเทสต์เต็ม | E-S10 | BE | ⚪ |
| **E-S12** | **ต่อยอด: ตั้งคณะกรรมการสภานักเรียนจากผลเลือกตั้ง** — สร้าง `AcademyGroup` type ใหม่ (`student_council`) จากพรรคที่ชนะ · **ต้องเพิ่ม type ทั้ง `AcademyGroupTypes.php` และ `ui/constants/academyGroupTypes.ts` (สองไฟล์นี้เป็น mirror กัน)** | E-S7 | BE + FE | ⚪ |

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

## 10. Review Log

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
