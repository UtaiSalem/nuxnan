# 27 — กีฬาสี (Sports Day / Color Houses)

> ไฟล์รองของเมนู **#27** — เมนูใหม่ ไม่อยู่ในสารบัญ 24 เมนูเดิมของ [OVERVIEW.md](OVERVIEW.md)
> สังกัด: **ฝ่ายบริหารงานกิจการนักเรียน → งานกิจกรรมนักเรียน** (ร่วมกับกลุ่มสาระสุขศึกษาและพลศึกษา)
> วันที่สแกน + เขียนสเปกร่าง: 2026-07-31 · **ยังไม่ล็อก** — รอตัดสิน §5

---

## 1. Reality Check

### 1.1 มี house leaderboard ต่อสายครบแล้ว แต่ใช้ไม่ได้ทั้งเส้น

ต่อกันครบตั้งแต่ route → controller → composable → UI แต่**ตายทั้งเส้น** ด้วยเหตุผล 2 ข้อที่ไม่เกี่ยวกัน:

| ชั้น | ไฟล์ |
|---|---|
| Controller | `app/Http/Controllers/Api/Learn/Academy/AnalyticsController.php:167-196` `houseLeaderboard()` · `:198-223` `classroomLeaderboard()` |
| Route | `routes/learn/academy.php:888-889` — `GET /gamification/leaderboard/houses` · `/classrooms` |
| Composable | `ui/composables/useSchoolManagement.ts:217-221` |
| UI | `ui/components/school/SchoolGamificationTab.vue:81-118` (แถวคณะสีอ่าน `settings['color']` + `settings['icon']` อยู่แล้ว) |

**🔴 ปัญหาที่ 1 — `type = 'house'` ไม่ได้ลงทะเบียน**

`AnalyticsController.php:176` query `AcademyGroup::where('type','house')` แต่ `app/Constants/AcademyGroupTypes.php:11-68` มีแค่ `office, department, section, academic_group, classroom, club, committee, dormitory` และ `ui/constants/academyGroupTypes.ts:24-33` ก็เหมือนกัน (สองไฟล์นี้เป็น mirror กัน มีคอมเมนต์กำกับว่าแก้ต้องแก้คู่)

→ **ไม่มี UI ไหนสร้างคณะสีได้เลย · ไม่มี seeder ไหนสร้าง · endpoint นี้คืน `[]` เสมอ**

ยืนยันจาก DB เครื่องนี้: `academy_groups` มี `office 1 · department 5 · section 21 · academic_group 8` — **ไม่มี `house` และไม่มี `classroom` แม้แต่แถวเดียว**

**🔴 ปัญหาที่ 2 — คะแนนคิดจากกระเป๋าเงินจริงของนักเรียน**

```php
// AnalyticsController.php:180-190
$totalPoints = DB::table('users')
    ->join('academy_group_members', ...)
    ->where('academy_group_members.academy_group_id', $house->id)
    ->sum('pp');   // ← pp = แต้มแพลตฟอร์มที่ถอนเป็นเงินได้
```

"คะแนนของคณะสี" = ผลรวมแต้มสะสมตลอดชีวิตของสมาชิกในระบบแพลตฟอร์ม → **ให้คะแนนชนะวิ่งกระสอบด้วยตัวนี้ = สร้างภาระจ่ายเงินจริง** และไม่มีทางให้คะแนน *แก่คณะสี* ได้เลย (ทุกเส้นทางให้แต้มยิงไปที่ `user_id`)

**นี่เป็นบั๊กเชิงออกแบบ ไม่ใช่ฟีเจอร์ที่ยังทำไม่เสร็จ** — ต้องเขียน `houseLeaderboard()` ใหม่ ไม่ใช่ต่อยอด

**🟡 ปัญหาที่ 3 — ไม่มีการจัดการคะแนนเท่ากัน** เรียงด้วย `sortByDesc('points')` แล้วใช้ index+1 เป็นอันดับ (ไม่มี leaderboard ตัวไหนในระบบทำเรื่องนี้เลย รวมถึง `FinalizeTypingTournaments`)

### 1.2 ของที่ใช้ต่อได้จริง

| ของเดิม | ใช้ทำอะไร |
|---|---|
| `academy_groups` + `academy_group_members` + `academy_group_admins` + `AcademyGroupPermission` | คณะสีคือกลุ่มแบบหนึ่ง → ลงทะเบียน type `house` แล้วได้ CRUD, สมาชิก, ประธานสี, ฟีดกลุ่ม, หน้าโปรไฟล์กลุ่ม **มาฟรีทั้งชุด** (`ui/components/academy/groups/` มีครบ) |
| `xp_events` + `classroom_point_cycles` + `ClassroomPointsService` / `XpService` (`app/Services/Gamification/`) | **event log ที่แก้ไม่ได้ + aggregate** — เป็นรูปที่ถูกต้องสำหรับคะแนนคณะสี · `xp_events.classroom_group_id` ชี้ `academy_groups` อยู่แล้ว → **ใช้กับ house ได้โดยไม่ต้องแก้ schema** ติดแค่ `ClassroomPointsService::leaderboard():89` hardcode `where('type','classroom')` |
| `SchoolEvent` — **`TYPE_SPORTS = 'sports'` → 'กีฬา' มีอยู่แล้ว** (`SchoolEvent.php:80,100`, mirror ที่ `ui/constants/schoolEventTypes.ts:23`) | ตัวครอบงานกีฬาสีทั้งงาน · มี `group_id`, `cover_image`, `attachments`, `status(draft/published/cancelled/completed)` |
| `ActivitySession` (`qr_token`, `slot_label`, `start_datetime`, `location`) | 1 session = 1 รายการแข่ง/1 คู่/1 ฮีต · ได้ QR เช็คชื่อนักกีฬามาด้วย |
| `TypingTournament` + `FinalizeTypingTournaments.php:29-45` | **lifecycle ที่ลอกได้ตรง ๆ**: `upcoming→active→finished` → finalize → persist rank → รางวัลแบ่งชั้น 1/2/3 + participation |
| `app/Services/PostMediaService.php` | อัปโหลดรูปหลายใบ + ย่อ 2048/400 + quality 85 — แม่แบบสำหรับอัลบั้มภาพงานกีฬา |
| `SchoolClassroomLeaderboard.vue` | สีเหรียญ `['#ffd700','#c0c0c0','#cd7f32']` |

### 1.3 ⛔ ห้ามใช้

- **`teams` / `team_user` / `team_invitations` + `users.current_team_id` + `ui/pages/Teams/`** — ซาก Jetstream **ไม่มี `Team` model เลย** ไม่ได้ต่อกับ API ใด
- **`ClassroomGroup`** — กลุ่มย่อย*ภายใน*ห้องเรียนห้องเดียว ข้ามห้องไม่ได้ → คณะสีใช้ไม่ได้
- **`points_transactions` / `users.pp`** — เงินจริง (ดู §1.1 ปัญหาที่ 2)
- **`Album` / `Photo`** — มีแค่ `user_id` ไม่มี `academy_id`/`event_id` → ผูกกับงานกีฬาไม่ได้ถ้าไม่แก้ schema

---

## 2. สิ่งที่ไม่มีเลยในระบบ (ต้องสร้างใหม่ทั้งหมด)

- **ไม่มี match / fixture / bracket / heat / round / standings** — ไม่มีการแข่งแบบตัวต่อตัวที่ไหนในระบบเลย (typing race เป็นการแข่งขนานแบบเรียลไทม์ ไม่ได้บันทึกผลคู่)
- **ไม่มี medal / placing / result** ในเชิงโดเมน — มีแค่สีเหรียญ hardcode ใน CSS และชั้นรางวัลของ typing tournament
- **ไม่มีทางให้คะแนนแก่กลุ่ม** ยกเว้น `XpService::award()` / `ClassroomPointsService::award()` (ซึ่งไม่เคยถูกใช้จริง — `academy_groups` ไม่มีแถว `classroom` เลย)
- **ไม่มีอัลบั้มภาพผูกกับกิจกรรมหรือกลุ่ม** · `SchoolEventController` **ไม่รับอัปโหลดไฟล์เลย** (`cover_image`/`attachments` คาดว่าเป็น path ที่อัปมาก่อนแล้ว)
- ไม่มีอะไรใน `.agents/` พูดถึงกีฬาสีมาก่อน — greenfield

---

## 3. โครงคะแนนอ้างอิงจากของจริง

จากเกณฑ์ตัดสินกีฬาสีของโรงเรียนนวมินทราชินูทิศ หอวัง ([ที่มา](https://sites.google.com/hwn.ac.th/hwngame/เกณฑ์การตัดสิน)) — ใช้เป็น **ค่าตั้งต้นที่ปรับได้** ไม่ใช่ค่าตายตัว:

| ประเภท | การให้คะแนน |
|---|---|
| กีฬา/กรีฑา **ประเภททีม** | ชนะเลิศ 9 → ลดหลั่นถึง 2 |
| กีฬา/กรีฑา **ประเภทเดี่ยว** | ชนะเลิศ 5 → ลดหลั่นถึง 2 |
| ขบวนพาเหรด | 100 คะแนน แยก 8 เกณฑ์ย่อย |
| กองเชียร์ + ผู้นำเชียร์ | 100 คะแนน แยก 5 เกณฑ์ย่อย |
| ตกแต่งอัฒจันทร์/สแตนด์ | 100 คะแนน แยก 3 เกณฑ์ย่อย |
| การบริหารคณะสี | 100 คะแนน (ความรวดเร็ว 20 · การแสดง 30 · ความพร้อมพิธีเปิด-ปิด 20 · การวางแผนพื้นที่ 30) |

→ **ข้อสรุปเชิงออกแบบ:** คะแนนคณะสีมาจาก **2 ทาง** ที่ต่างกันโดยสิ้นเชิง — (ก) ผลการแข่งขันที่แปลงจากอันดับเป็นคะแนนตามตาราง (ข) คะแนนจากคณะกรรมการให้ตามเกณฑ์ย่อย · ระบบต้องรองรับทั้งสองทาง และทั้งสองต้องลงเป็น event log เดียวกันเพื่อให้ย้อนดูที่มาของคะแนนทุกแต้มได้

---

## 4. โครงสร้างที่เสนอ (ร่าง)

> ⚠️ **แก้ 2026-08-08 ตาม S-D2 รอบใหม่** — หน่วยขอบเขตไม่ใช่ "ปีการศึกษา" อีกต่อไป แต่เป็น **`sports_editions` = งานกีฬาสี 1 ครั้ง** (ดู §5.4) · ทุกที่ที่แผนภาพนี้เขียน `SchoolEvent` ให้อ่านว่า `sports_editions` ซึ่ง**อาจ**ผูกกับ `school_events` หรือไม่ก็ได้

```
sports_editions                            ← งานกีฬาสี 1 ครั้ง (จัดกี่ครั้ง/ปีก็ได้)
  └─ sports_edition_houses → AcademyGroup(type='house')  ← คณะสีของครั้งนี้ (จำนวนอยู่ที่จำนวนแถว)
  └─ sports_disciplines                     ← ชนิดกีฬา (ฟุตบอล, วิ่ง 100 ม., ชักเย่อ, พาเหรด, กองเชียร์)
       │    is_team / is_judged / scoring_table (json)
       └─ sports_matches  →  ActivitySession  ← 1 คู่/1 ฮีต/1 รอบ (ได้ QR เช็คชื่อนักกีฬาฟรี)
            └─ sports_match_results          ← house_id, placing, raw_score, recorded_by
  └─ sports_score_entries                    ← event log ทุกแต้ม: house_id, source, points, ref
  └─ sports_house_standings                  ← aggregate (คะแนนรวม, เหรียญทอง/เงิน/ทองแดง, อันดับ)
```

**หลักการที่ต้องยึด (มาจากบทเรียนของ §1.1):**
1. **คะแนนกีฬาสีต้องเป็นสกุลของตัวเอง แยกขาดจาก `users.pp`** — ห้ามแตะกระเป๋าเงิน
2. ทุกแต้มต้องมี **แถว event log ที่อธิบายที่มาได้** (ใครให้ เมื่อไหร่ จากรายการไหน) — ไม่ใช่ตัวเลขรวมที่แก้ทับได้
3. `sports_house_standings` เป็น aggregate ที่คำนวณใหม่ได้จาก event log เสมอ ไม่ใช่แหล่งความจริง
4. **ต้องจัดการคะแนนเท่ากัน** (อันดับร่วม) ตั้งแต่แรก — ระบบเดิมไม่มีที่ไหนทำ และกีฬาสีคะแนนเท่ากันเกิดบ่อย

---

## 5. ข้อตัดสิน — ล็อกแล้ว 2026-08-02 (ผู้ใช้ตัดสิน)

| # | ประเด็น | ผลตัดสิน |
|---|---|---|
| **S-D1** | วิธีแบ่งนักเรียนเข้าคณะสี | ✅ **รองรับทั้งสุ่มและนำเข้า** — ทั้งสองโหมดต้องเดินผ่าน **เส้นทางเดียวกัน** (batch → preview → commit → undo) ไม่ใช่สองฟีเจอร์แยก |
| **S-D2** | กี่คณะสี | ✅ **ตัดสินแล้ว 2026-08-08 — ไม่มีจำนวนตายตัว** จำนวนคณะสีกำหนดได้ **แล้วแต่สถาบันการศึกษา** และ **แล้วแต่ครั้งที่จัด** → จำนวน = จำนวนแถว `sports_edition_houses` ของครั้งนั้น (ดู §5.4) · ยัง**ห้าม seed คณะสีตัวอย่าง** · ครั้งแรกของจริง: **ปี 2569 ใช้ 4 คณะสี** |
| **S-D3** | bracket | ✅ **ตัดสินแล้ว 2026-08-17 — รองรับครบทุกรูปแบบรวมกรีฑา** (แพ้คัดออก · พบกันหมด · ฮีตจับเวลา) พร้อมตัวสร้างคู่อัตโนมัติ · **อันดับสุดท้ายระบบเสนอ ครูกดยืนยัน** ไม่ลงคะแนนเอง · แมตช์เก็บช่อง `activity_session_id` ไว้แต่ยังไม่ทำ QR เช็คชื่อ (ดู §12) |
| **S-D4** | นักกีฬาสมัครเอง | ⏸ ยังไม่ถึงคิว |
| **S-D5** | คะแนนโผล่ที่ไหน | ⏸ ยังไม่ถึงคิว |
| **S-D6** | ปีถัดไป | ✅ **แบ่งใหม่ทุกครั้งที่จัด** (เดิมเขียนว่า "ทุกปี" — ขยายความ 2026-08-08 เพราะจัดได้หลายครั้ง/ปี) → สมาชิกภาพคณะสี**ผูกกับ `sports_editions` ไม่ใช่ `academic_year_id`** · ปีการศึกษาเป็นคุณสมบัติของ edition |
| **S-D7** *(ใหม่)* | เกณฑ์กระจายของตัวสุ่ม | ✅ ค่าเริ่มต้น = **คละทุกห้องเท่า ๆ กัน + สมดุลชาย/หญิง** · และต้องมีตัวเลือก **สุ่มล้วนทั้งโรงเรียน** ให้เลือกตอนรัน |
| **S-D8** *(ใหม่ 2026-08-17)* | คะแนนเท่ากัน (อันดับร่วม) | ✅ **ได้คะแนนเท่ากัน แล้วข้ามอันดับถัดไป** (`1,1,3`) ทั้งตอนแปลงอันดับเป็นคะแนนและตอนจัดอันดับตารางรวม · ไม่มี tie-breaker อัตโนมัติ (ดู §10.2) |

### 5.1 ผลที่ตามมาจาก S-D6 — ห้ามเก็บสมาชิกคณะสีใน `academy_group_members`

`academy_group_members` **ไม่มี `academic_year_id`** (คอลัมน์จริง: `id, academy_group_id, user_id, role, status, invited_by, timestamps`) และตอนนี้ตารางนี้ให้บริการ 35 กลุ่มที่มีอยู่จริง (office 1 · department 5 · section 21 · academic_group 8)

→ **ห้ามเพิ่มคอลัมน์ปีลงตารางกลาง** เพราะทุก query ของฝ่าย/งาน/กลุ่มสาระจะต้องรู้จักค่า null ทันที (บทเรียนเดียวกับ `Student::guardians()` ใน #6 §6.1)
→ ใช้ตารางของตัวเอง `house_memberships` ที่มี `academic_year_id` ตั้งแต่แรก · `academy_groups(type='house')` ยังเป็น **ตัวตนถาวรข้ามปี** ของคณะสี (แดง/น้ำเงิน/…) จึงได้ CRUD/ฟีด/หน้าโปรไฟล์กลุ่มมาฟรีตามเดิม

### 5.2 🔴 S-S2 ต้องอยู่ commit เดียวกับ S-S1 (เปลี่ยนลำดับจากร่างเดิม)

วันนี้ `houseLeaderboard()` คืน `[]` **เพราะยังไม่มีแถว `house` เท่านั้น** — ไม่ใช่เพราะปลอดภัย
**วินาทีที่ S-S1 หรือเครื่องมือแบ่งสีสร้างคณะสีแถวแรก** endpoint นี้จะเริ่มเผยแพร่ `SUM(users.pp)` = ยอดเงินถอนได้ของนักเรียนทั้งคณะทันที
และ route `routes/learn/academy.php:895` อยู่ในบล็อก `auth:api` เปล่า ๆ **ไม่มี `academy.permission` สักตัว** (รูปแบบเดียวกับช่องโหว่ departments ที่ปิดไปใน #9 D-S1) → ใครล็อกอินก็เรียกได้

### 5.3 บล็อกเกอร์ที่สเปกร่างมองข้าม — validation ไม่ได้อ่านจาก constant

`AcademyGroupController::store():46` และ `update():87` **hardcode** `in:office,department,section,academic_group,classroom,club,committee`
→ ลิสต์นี้ **หลุดจาก `AcademyGroupTypes` ไปแล้ว** (`dormitory` อยู่ใน constant แต่ API ปฏิเสธ)
→ เติม `house` ใน 2 mirror เฉย ๆ **ยังสร้างคณะสีไม่ได้** ต้องแก้ให้ rule อ่านจาก `AcademyGroupTypes::keys()` (ซึ่งซ่อม `dormitory` ให้ด้วยในตัว)

### 5.4 🔴 ผลที่ตามมาจาก S-D2 รอบใหม่ — หน่วยขอบเขตต้องย้ายจาก "ปี" ไป "ครั้งที่จัด" *(ใหม่ 2026-08-08)*

**"แล้วแต่สถาบันการศึกษา" ได้อยู่แล้ว** — คณะสีคือ `academy_groups(type='house', academy_id)` ไม่มีเลขตายตัวที่ไหน และ `house_group_ids` ส่งมาต่อ batch ได้อิสระ (UI ติ๊กกี่สีก็ได้ ขั้นต่ำ 2)

**"แล้วแต่ครั้งที่จัด" ยังทำไม่ได้ ติดบรรทัดเดียว** — `2026_08_02_000003_create_house_memberships_table.php:26`

```php
$table->unique(['academic_year_id', 'student_id']);   // 1 นักเรียน = 1 สี ต่อ 1 ปี ตายตัว
```

จัด 2 ครั้งในปีเดียวด้วยชุดสีคนละชุดไม่ได้ เพราะ `commit()` ใช้ `upsert` ที่คีย์นี้ (`HouseAssignmentService.php:163`) → **ครั้งที่ 2 ทับครั้งที่ 1 ทิ้ง** และ `current()` (`HouseAssignmentController.php:36`) กับ `HouseMembershipProjector::rebuild($academyId, $yearId)` ก็นับ/ฉาย**รายปี**ทั้งคู่ = ยึดสมมติฐาน "1 ปี 1 ชุดสี" ไว้ทั้งเส้น

⏱ **ต้องแก้ก่อน S-S4 เท่านั้น** — วันนี้ `house_memberships` / `house_assignment_batches` / `house_assignment_rows` = **0 แถวทั้ง 3 ตาราง** (ยืนยัน 2026-08-08) เปลี่ยนคีย์ได้ฟรี · ถ้าปล่อยให้ `sports_score_entries` ลงไปก่อนแล้วค่อยย้าย จะต้องย้าย event log คะแนนตามไปด้วย

**⛔ ห้ามใช้ `school_events` เป็นคีย์ของสมาชิกภาพคณะสีโดยตรง** — ตรวจตารางจริงแล้ว: **ไม่มีคอลัมน์ `academic_year_id`** (จึงดึงประชากร §7.4 ไม่ได้), มี `deleted_at` (soft delete แล้วสมาชิกภาพคณะสีลอย), และให้บริการ event_type หลายแบบปนกันอยู่แล้ว (มี 3 แถว seed: sports / meeting / ceremony)
→ ใช้ตาราง `sports_editions` ของตัวเอง แล้ว**ผูกกลับ**ด้วย `school_event_id` แบบ nullable เพื่อให้ได้ QR เช็คชื่อนักกีฬาตาม §4 เมื่อผู้ใช้อยากผูก

---

## 6. Implementation Tasks (ล็อกแล้ว)

| Step | Title | Depends | Status |
|---|---|---|---|
| **S-S1+S-S2** | **ก้อนเดียวกัน (ดู §5.2)** — ลงทะเบียน type `house` ใน `app/Constants/AcademyGroupTypes.php` + `ui/constants/academyGroupTypes.ts` · เปลี่ยน `in:` rule 2 จุดให้อ่าน `AcademyGroupTypes::keys()` · **เขียน `houseLeaderboard()` ใหม่ให้เลิกอ่าน `SUM(users.pp)`** · ใส่ `academy.permission` ให้ 2 route leaderboard | — | ✅ `b2fb8e40` (5 เทสต์) |
| **S-S3** | เครื่องมือแบ่งนักเรียนเข้าคณะสี — **โหมดสุ่ม** + batch/commit/undo/projection ผ่านเส้นทางเดียวกัน (§7) | S-S1 | ✅ `f5fe814e` (16 เทสต์) |
| **S-S3i** | **โหมดนำเข้า** — parser + matcher บนตารางและ commit path เดียวกัน (§7.6) | S-S3 | ✅ (9 เทสต์) |
| **S-S3b** | หน้าจอแบ่งคณะสี (เลือกโหมด → preview → commit → undo) + เมนูใน admin.vue | S-S3i | ✅ `f065ce19` |
| **S-S3e** *(ใหม่ 2026-08-08 — แทรกก่อน S-S4)* | **หน่วย "ครั้งที่จัด"** — ตาราง `sports_editions` + `sports_edition_houses` · ย้ายคีย์ของ `house_memberships`/`house_assignment_batches` จาก `academic_year_id` → `edition_id` · projector ฉายจาก edition ที่ `active` เท่านั้น (§9) | S-S3b | ✅ `d89f9796` (backend+schema) + `2a348218` (หน้าจอ) — **ยืนยันสถานะจริง 2026-08-17** ดู §9.6 |
| **S-S4** | schema กีฬาสี (§4) + ให้คะแนนแก่คณะสีผ่าน event log + จัดการคะแนนเท่ากัน — **ทุกตาราง key ที่ `edition_id`** | **S-S3e** | ⚪ **ไม่มีอะไรบล็อกแล้ว** (S-S3e เสร็จ) |
| **S-S5a** | schema แมตช์ (`sports_matches` + `sports_match_participants`) + CRUD คู่แข่ง + บันทึกผลรายแมตช์ (§12.2–12.3) | S-S4 | ⚪ **ไม่มีอะไรบล็อกแล้ว** (S-D3 ตัดสินแล้ว) |
| **S-S5b** | ตัวสร้างคู่อัตโนมัติ 3 รูปแบบ + เลื่อนสายแพ้คัดออก (§12.4) | S-S5a | ⚪ |
| **S-S5c** | ตัวเสนออันดับ + หน้ายืนยันอันดับ → ลง event log คะแนน (§12.5) | S-S5b | ⚪ |
| **S-S6a** *(แยกใหม่ 2026-08-17)* | หน้าจอชุดแรกที่ผู้ใช้เห็นของจริง: ตารางคะแนนคณะสี · จัดการรายการแข่ง · ให้คะแนนด้วยมือ · ประวัติคะแนน/ยกเลิก — **ทำได้เลยด้วยของที่ S-S4 มีอยู่ ไม่ต้องรอ S-S5** (§11) | **S-S4** | 🟡 กำลังทำ |
| **S-S6b** | หน้าจอ: ตารางแข่ง/สายการแข่ง · กรอกผลรายแมตช์ · จอยืนยันอันดับ · สรุปเหรียญละเอียด | S-S5c | ⏸ |
| **S-S7** | อัลบั้มภาพผูกกับงาน (ต้องเพิ่ม owner ให้ `albums` หรือทำตารางใหม่ — ดู §1.3) | S-S6b | ⚪ |

---

## 7. S-S3 — เครื่องมือแบ่งนักเรียนเข้าคณะสี (สเปกล็อก)

### 7.1 หลักการ: สุ่มกับนำเข้าต่างกันแค่ "ใครเป็นคนกรอกช่องคณะสี"

ทั้งสองโหมดสร้าง **batch + rows ชุดเดียวกัน** แล้วเดินต่อด้วยโค้ดเส้นเดียวกัน:

```
POST preview (mode=random + options)  ─┐
POST preview (mode=import + file)     ─┴→ house_assignment_batches(status=draft)
                                           + house_assignment_rows (ทุกแถวมีสถานะรายแถว)
                                        → GET rows (แบ่งหน้า + สรุปจำนวนต่อสถานะ/ต่อสี)
                                        → POST commit  → เขียน house_memberships
                                        → POST undo    → ย้อนได้ตามรูป RolloverBatch::isUndoable()
```

**ทำไมต้องเส้นเดียว:** โหมดนำเข้าต้องให้คนตรวจก่อนลงจริงอยู่แล้ว (จับคู่ชื่อไม่ได้/สีไม่รู้จัก) และโหมดสุ่มก็ต้องให้คนดูยอดต่อสีก่อนกด — ถ้าแยกเส้นจะได้ตัว commit 2 ตัวที่ต้องถูกต้องตรงกันตลอดไป

### 7.2 ตารางใหม่ 3 ตาราง

```
house_assignment_batches
  id uuid PK · academy_id · academic_year_id · mode enum(random|import)
  status enum(draft|committed|undone) · options json · summary json
  source_filename nullable · created_by_user_id
  committed_at/committed_by_user_id · undone_at/undone_by_user_id · timestamps
  index [academy_id, academic_year_id, status]

house_assignment_rows
  id · batch_id FK cascade · row_number
  raw json (แถวดิบจากไฟล์ / null ถ้าโหมดสุ่ม)
  student_id nullable FK · house_group_id nullable FK
  previous_house_group_id nullable FK   ← สีเดิม ณ ตอน preview (ดู §7.3.1)
  status enum(ok|unmatched|ambiguous|unknown_house|already_assigned|skipped)
  message nullable · timestamps
  index [batch_id, status]

house_memberships                       ← แหล่งความจริงของ "ใครอยู่สีอะไร ปีไหน"
  id · academy_id · academic_year_id · house_group_id FK · student_id FK
  user_id nullable · source enum(random|import|manual) · batch_id nullable
  assigned_by_user_id · timestamps
  UNIQUE [academic_year_id, student_id]  ← กันคนเดียวอยู่ 2 สีในปีเดียว
  index [academy_id, academic_year_id, house_group_id]
```

### 7.3.1 ⚠️ undo ต้อง "คืนสีเดิม" ไม่ใช่ "ลบทิ้ง" (แก้สเปกเดิม 2026-08-02)

ร่างแรกของ §7 เขียนว่า undo = ลบแถว `house_memberships` ที่มี `batch_id` นั้น — **ผิด**

เพราะ commit ของ `scope=all` ใช้ upsert ทับแถวเดิม → **สีเดิมของคนที่ถูกย้ายหายไปตั้งแต่ตอน commit** พอกด undo จึงได้ผลลัพธ์ว่าคนกลุ่มนั้น**ไม่มีสังกัดสีเลย** แทนที่จะกลับไปสีเดิม (ยิ่งอันตรายเพราะปุ่มชื่อ "ย้อนกลับ")

→ เก็บ `previous_house_group_id` ลง `house_assignment_rows` ตั้งแต่ตอน preview
→ `undo()`: ถ้า `previous_house_group_id` ไม่ null ให้**เขียนกลับเป็นสีเดิม** · ถ้า null (คนที่เพิ่งได้สีครั้งแรกจาก batch นี้) จึงลบแถวทิ้ง
→ ต้องมีเทสต์: แบ่งเข้าสี A → แบ่งใหม่ `scope=all` ให้ไปสี B → undo → **ต้องกลับมาอยู่สี A ไม่ใช่ไม่มีสี**

### 7.3 การฉายไปยัง `academy_group_members` (projection ไม่ใช่ dual-write)

หลัง commit **ของปีปัจจุบันเท่านั้น** ให้ `HouseMembershipProjector` เขียน `academy_group_members` ของกลุ่ม `house` ใหม่ทั้งชุดจาก `house_memberships` (delete+insert ในทรานแซกชัน) เพื่อให้ฟีดกลุ่ม/หน้าโปรไฟล์คณะสีที่มีอยู่แล้วใช้งานได้

- **ผู้เขียนมีตัวเดียว** — ห้ามมีเส้นทางอื่นเขียน `academy_group_members` ของกลุ่ม type `house`
- ⚠️ **คอลัมน์จริงของ `academy_group_members` มีแค่** `id, academy_group_id, user_id, role, status, invited_by, timestamps` — **ไม่มี `student_id`** (พิสูจน์แล้วด้วยการรันจริง: insert ที่ใส่ `student_id` ตาย `SQLSTATE[42S22]`) · และ `role` ต้องเป็น `'member'` ไม่ใช่ `'student'` (migration `2026_07_29_000001_change_academy_group_member_role_default` เปลี่ยน default ทิ้งไปแล้วเพราะ `'student'` ไม่ตรงกับ validation ใด ๆ)
- สร้างใหม่จากแหล่งความจริงได้เสมอ (idempotent) → ไม่ใช่กับดัก dual-write แบบ #6
- นักเรียนที่ยังไม่มี `user_id` จะไม่มีแถว projection (ตารางกลางคีย์ที่ `user_id`) แต่**ยังอยู่ใน `house_memberships` ครบ** — ยอดคนต่อสีต้องนับจาก `house_memberships` เท่านั้น ห้ามนับจากตารางกลาง

### 7.4 ประชากรที่ถูกแบ่ง

`classroom_students` ของปีนั้น `status='active'` → **2,202 คนในปี 2569** (ม.1 397 · ม.2 455 · ม.3 370 · ม.4 377 · ม.5 312 · ม.6 291)

**ห้ามใช้ `students.status='active'` (2,662 คน) เป็นประชากร** — ต่างกัน 460 คนคือคนที่ไม่มีแถวห้องเรียนปีนี้ (สายเดียวกับ 449 คนที่ถูกจำหน่ายใน #25) → ถ้าใช้ตัวหลังจะแบ่งคนที่ไม่ได้เรียนอยู่เข้าคณะสีด้วย

### 7.5 โหมดสุ่ม — 2 กลยุทธ์ (S-D7)

**`strategy=stratified` (ค่าเริ่มต้น)** — คละทุกห้องเท่า ๆ กัน
1. ไล่ทีละห้อง (`classrooms` ของปีนั้น)
2. ในห้อง ถ้า `balance_gender=true` แยกเป็นถัง ชาย / หญิง / **ไม่ระบุ** (มี 227 คนที่ `students.gender` เป็น null — ต้องเป็นถังที่ 3 ห้ามยัดเข้าถังใดถังหนึ่ง)
3. สับแต่ละถังด้วย seed แล้วแจกแบบ round-robin เข้าคณะสี โดย**เริ่มที่คณะสีที่มีคนน้อยที่สุด ณ ตอนนั้น** (ไม่ใช่เริ่มที่สีแรกเสมอ ไม่งั้นสีต้น ๆ จะได้เศษของทุกห้อง)

**`strategy=pure_random`** — สุ่มล้วนทั้งโรงเรียน ไม่สนห้อง/ระดับชั้น

**ต้องมีทั้งคู่:**
- `seed` บันทึกใน `options` → รันซ้ำได้ผลเดิม อธิบายให้นักเรียนที่สงสัยได้ว่าไม่ได้เลือกที่รัก
- `scope` = `unassigned_only` (ค่าเริ่มต้น — เติมเฉพาะคนที่ยังไม่มีสีในปีนั้น) หรือ `all` (แบ่งใหม่ทั้งหมด ทับของเดิม) · โหมด `all` ต้องเตือนจำนวนคนที่จะถูกย้ายสีในหน้า preview

### 7.6 โหมดนำเข้า — การจับคู่

รับ `.xlsx`/`.csv` · ผู้ใช้แม็พคอลัมน์เอง (รูปเดียวกับ `student_import_batches.column_mapping`)

จับคู่นักเรียนตามลำดับ **หยุดที่ตัวแรกที่เจอ**:
1. `students.student_id` (เลขประจำตัวนักเรียน) — **ยืนยันแล้วว่าไม่ซ้ำเลย 0 คู่ ว่าง 1 แถว** → เป็นคีย์หลักที่เชื่อถือได้
2. `students.citizen_id` 13 หลัก — ⚠️ **ห้ามใช้ถ้าไม่ครบ 13 หลัก** มี 215 แถวที่ Excel ทำพังเป็น `1.90E+12` และค่าเดียวซ้ำ 72 แถว (บันทึกไว้ใน #6 G-S0) → จับคู่พลาดจะยกคน 72 คนเข้าสีเดียวกัน
3. ชื่อ+นามสกุล เทียบแบบไม่สนวรรณยุกต์ `[่้๊๋]` (ใช้ normalizer ตัวเดียวกับ `SelectsGuardianRelation` / guardians backfill) — **ถ้าเจอมากกว่า 1 คน ต้องเป็น `ambiguous` ห้ามเดา**

จับคู่คณะสี: เทียบ `academy_groups.name` ของ type `house` แบบ trim + case-insensitive · ไม่เจอ = `unknown_house` (**ห้ามสร้างคณะสีใหม่อัตโนมัติจากไฟล์**)

`already_assigned` = มีสีในปีนั้นอยู่แล้ว → ค่าเริ่มต้น `skip`, มีตัวเลือก `overwrite` ใน options (โหมด overwrite ต้องเก็บ `previous_house_group_id` เพื่อให้ undo คืนสีเดิมได้ ตาม §7.3.1)

**นักเรียนคนเดียวโผล่ 2 แถวในไฟล์** → แถวหลังต้องเป็น `ambiguous` พร้อมบอกเลขแถวแรก **ห้ามปล่อยเป็น `ok` ทั้งคู่** เพราะ `commit()` ใช้ upsert → DB ได้แถวเดียว แต่**หน้า preview จะรายงานยอดเกินจริง** และยอดต่อสีที่คนอนุมัติไปจะไม่ตรงกับที่เขียนจริง (พิสูจน์ด้วยเทสต์: ปิดตัวกันซ้ำแล้วยอด ok = 3 ทั้งที่มีนักเรียน 2 คน)

⚡ **ประสิทธิภาพ:** ตัวจับคู่ต้องสร้างดัชนี (รหัสนักเรียน / เลขบัตร 13 หลัก / ชื่อ-สกุล normalize / ชื่อคณะสี / สังกัดเดิมของปีนั้น) **ครั้งเดียวต่อการนำเข้า** — ไฟล์จริง ~2,200 แถว × นักเรียน ~2,900 คน ถ้า query รายแถวจะกลายเป็นการโหลดตารางนักเรียนทั้งตาราง 2,200 รอบ

### 7.7 สิทธิ์

เพิ่มตระกูล `sports` ใน `AcademyPermission::PERMISSIONS` (`sports.view` / `sports.manage`) + ใส่ `'sports'` ใน `DEPARTMENT_DELEGABLE_FAMILIES` (ฝ่ายกิจการนักเรียนต้องมอบต่อได้ ตามรูปของ `'elections'`)
⚠️ เติมคำเดียว **ห้ามเผลอใส่ซ้ำ 2 ครั้ง** — PHP เก็บตัวหลังเงียบ ๆ เทสต์ไม่จับ (เคสจริงของ E-S1)
ทุก route ของ S-S3 ต้องมี `academy.permission:sports.manage` (อ่านอย่างเดียวใช้ `sports.view`)

---

## 8. Review Log

- **2026-07-31 — สแกน + เขียนสเปกร่าง** — พบว่าเมนูนี้มี house leaderboard ต่อสายครบตั้งแต่ route ถึง UI แต่ **ตายทั้งเส้นด้วยเหตุผล 2 ข้อที่ไม่เกี่ยวกัน**: `type='house'` ไม่ได้อยู่ใน registry (สร้างไม่ได้) และคะแนนคิดจาก `SUM(users.pp)` ซึ่งเป็นเงินจริง (ใช้ไม่ได้แม้สร้างได้) · ยืนยันจาก DB: `academy_groups` ไม่มีแถว `house` และไม่มีแถว `classroom` เลย → **เส้นทางให้คะแนนแบบกลุ่มทั้งเส้นไม่เคยถูกรันจริง** ต้องนับ `xp_events`/`ClassroomPointsService` เป็น "เขียนแล้วแต่ยังไม่พิสูจน์" เหมือนเมนู #26 · ยังไม่ล็อกแผนเพราะ §5 มี 6 ข้อค้าง โดยเฉพาะ S-D1 (วิธีแบ่งนักเรียน) ที่เปลี่ยนงานมากที่สุด

---

## 9. S-S3e — หน่วย "ครั้งที่จัด" (สเปกล็อก 2026-08-08)

### 9.1 ตารางใหม่ 2 ตาราง

```
sports_editions                       ← งานกีฬาสี 1 ครั้ง = หน่วยขอบเขตของทุกอย่างใน #27
  id · academy_id FK cascade
  academic_year_id FK cascade         ← ยังต้องมี เพราะประชากรมาจาก classroom_students ของปีนั้น (§7.4)
  school_event_id nullable FK school_events nullOnDelete   ← ผูกงาน/QR เช็คชื่อ (§4) ไม่บังคับ
  name varchar(150)                   ← 'กีฬาสี 2569'
  sequence unsigned smallint default 1 ← ครั้งที่เท่าไหร่ในปีนั้น
  status enum(draft|active|closed) default 'draft'
  starts_on/ends_on date nullable
  created_by_user_id FK users
  timestamps
  UNIQUE [academy_id, academic_year_id, sequence]
  active_key AS (IF(status='active', academy_id, NULL)) STORED + UNIQUE  ← กัน active ซ้อนกัน (ดู 9.4)

sports_edition_houses                 ← "ครั้งนี้มีกี่สี อะไรบ้าง" — คำตอบของ S-D2 อยู่ที่จำนวนแถวตรงนี้
  id · edition_id FK cascade · house_group_id FK academy_groups cascade
  display_order unsigned smallint default 0
  timestamps
  UNIQUE [edition_id, house_group_id]
```

**ทำไมต้องมี `sports_edition_houses` ทั้งที่ batch ก็เก็บ `house_group_ids` อยู่แล้ว** — batch คือ *การรันเครื่องมือ 1 ครั้ง* (รันกี่รอบก็ได้ต่อ edition) ส่วนตารางนี้คือ *ชุดสีที่เป็นทางการของงานครั้งนั้น* · S-S4 ต้องใช้ตัวหลัง เพราะตารางคะแนนต้องแสดง**ทุกคณะสีรวมถึงคณะที่ยังได้ 0 แต้ม** ซึ่งอนุมานจาก event log ไม่ได้

### 9.2 การย้ายคีย์ (ตาราง 0 แถวทั้งหมด → ทำได้ครั้งเดียวจบ)

| ตาราง | เดิม | ใหม่ |
|---|---|---|
| `house_memberships` | `academic_year_id` + `UNIQUE [academic_year_id, student_id]` | **`edition_id`** + `UNIQUE [edition_id, student_id]` · index `[edition_id, house_group_id]` |
| `house_assignment_batches` | `academic_year_id` · index `[academy_id, academic_year_id, status]` | **`edition_id`** · index `[edition_id, status]` |
| `house_assignment_rows` | — (ผูกกับ batch อยู่แล้ว) | ไม่เปลี่ยน |

- **`academic_year_id` ต้องถูก drop ออกจาก 2 ตารางบน ไม่ใช่เก็บไว้คู่กัน** — เจ้าของปีมีคนเดียวคือ `sports_editions` ถ้าเก็บทั้งคู่จะเปิดช่องให้ปีของ membership ขัดกับปีของ edition (บทเรียน dual-write ของ #6)
- `academy_id` **เก็บไว้ได้** เพื่อ scope 404 ตามรูปเดิมของ `HouseAssignmentController::scoped()` แต่ **ต้องเขียนจาก `$edition->academy_id` เท่านั้น ห้ามรับจาก request**
- 🔧 **วิธีทำ migration ที่ปลอดภัยที่สุดคือ drop + recreate ทั้ง 3 ตาราง** ไม่ใช่ ALTER — เลี่ยงกับดัก `dropForeign()->dropColumn()` ที่ต่อกันไม่ได้ (บันทึกไว้แล้วในบันทึก 2026-08-02) และเลี่ยงเรื่อง index ที่ FK ยืมใช้
  → **ต้องมี guard: ถ้าตารางใดมีแถว > 0 ให้ `throw` พร้อมข้อความชัด** ห้ามลบข้อมูลของ environment ที่เผลอใช้ไปแล้ว
  → `down()` ต้องสร้างรูปเดิมกลับได้จริง

### 9.3 จุดที่ต้องแก้ตาม (สำรวจแล้ว 2026-08-08)

| ไฟล์ | สิ่งที่ต้องเปลี่ยน |
|---|---|
| `HouseAssignmentService.php` (13 จุดที่อ้าง academic_year) | `previewRandom(Academy, int $year, …)` / `previewImport(…)` → รับ `SportsEdition` แทน `int $year` · ปีอ่านจาก `$edition->academic_year_id` · **ตรวจ `house_group_ids` กับ `sports_edition_houses` ของ edition นั้น ไม่ใช่กับทั้ง academy** · `commit()` upsert คีย์ `['edition_id','student_id']` |
| `HouseAssignmentController.php` (8 จุด) | validation `academic_year_id` → `edition_id` ทุก route · `current()` นับต่อ edition |
| `HouseMembershipProjector.php` | `rebuild($academyId, $yearId)` → `rebuild(SportsEdition $edition)` — ฉายเฉพาะ edition ที่ `status='active'` (ดู 9.4) |
| `HouseImportMatcher.php` | `match(Academy, int $year, …)` → รับ edition · เงื่อนไข `already_assigned` เทียบสีเดิม**ในครั้งนั้น** ไม่ใช่ในปีนั้น |
| `ui/composables/useHouseAssignments.ts` + `ui/pages/academies/[name]/admin/house-assignments/index.vue` | ตัวเลือกปีการศึกษา → **ตัวเลือก "ครั้งที่จัด"** + จอจัดการ edition (สร้าง/เลือกคณะสีของครั้งนี้/เปิดใช้/ปิด) |
| `tests/Feature/Sports/HouseAssignmentTest.php` · `HouseImportTest.php` (25 เทสต์) | factory ต้องสร้าง edition ก่อน |

### 9.4 กฎ projection ที่ต้องล็อก — ห้ามมี edition `active` เกิน 1 ต่อ academy

`academy_group_members` ของกลุ่ม type `house` ตอบคำถามเดียวว่า **"ตอนนี้ใครอยู่สีอะไร"** จึงต้องมีต้นทางเดียว
→ ฉายจาก edition ที่ `status='active'` เท่านั้น · เปลี่ยน edition เป็น `active` = projector rebuild ใหม่ทั้งชุด · ไม่มี edition `active` = ตาราง projection ว่าง (ถูกต้อง ไม่ใช่บั๊ก)
→ บังคับด้วย **generated column `active_key` + UNIQUE** เพราะ MySQL ไม่ถือว่า `NULL` ซ้ำกัน — เป็นเทคนิคเดียวกับที่บันทึกไว้ให้ `election_results.party_key` ใน #25 · **อย่าบังคับด้วยโค้ดแอปอย่างเดียว**

### 9.5 สิ่งที่ **ดีขึ้นฟรี** จากการย้ายคีย์ (ไม่ใช่แค่หนี้ที่ต้องจ่าย)

- **ประวัติไม่ถูกทับ** — เดิมแบ่งใหม่ในปีเดียวกัน `upsert` กลืนสีเดิมทิ้ง เหลือร่องรอยแค่ใน `house_assignment_rows` · ตอนนี้แต่ละครั้งมีแถวของตัวเอง ย้อนดูได้ว่าปีที่แล้วเด็กคนนี้อยู่สีอะไร
- **`previous_house_group_id` (§7.3.1) ความหมายชัดขึ้น** — "สีเดิมภายในครั้งนี้" ไม่ใช่ "สีเดิมที่อาจมาจากงานคนละงาน"
- **เปลี่ยนจำนวนสีระหว่างครั้งไม่ต้องคิดเรื่องคนตกค้าง** — ครั้งใหม่เริ่มจากศูนย์เสมอ ไม่มีเคส "สีที่ถูกยุบยังมีสมาชิกค้าง"

### 9.6 สถานะจริงที่ตรวจสอบแล้ว (2026-08-17) — S-S3e **ทำเสร็จไปแล้ว**

ตารางสถานะใน §6 เคยค้างเป็น ⚪ อยู่ ทั้งที่งานลงไปแล้วตั้งแต่ commit `d89f9796` + `2a348218` (อยู่บน `origin/main` แล้วทั้งคู่)
หลักฐานที่รันจริงบนเครื่อง dev:

| ตรวจ | ผล |
|---|---|
| `php artisan migrate:status` | `2026_08_08_000001_create_sports_editions_tables` = Ran batch 118 · `2026_08_08_000002_rekey_house_tables_to_edition` = Ran batch 119 |
| schema `sports_editions` | มีครบ + `active_key` เป็น generated column พร้อม `se_active_key_unique` |
| schema `sports_edition_houses` | UNIQUE `[edition_id, house_group_id]` ✔ |
| schema `house_memberships` | `edition_id` · UNIQUE `[edition_id, student_id]` · index `[edition_id, house_group_id]` · **ไม่มี `academic_year_id` แล้ว** ✔ |
| schema `house_assignment_batches` | `edition_id` · index `[edition_id, status]` · **ไม่มี `academic_year_id` แล้ว** ✔ |
| backend | `HouseAssignmentService` / `HouseImportMatcher` / `HouseMembershipProjector` / `HouseAssignmentController` รับ `SportsEdition` แล้ว · ปีอ่านจาก `$edition->academic_year_id` ตอน query `classroom_students` ตามสเปก |
| routes | `{academy}/sports-editions` index/store/update/activate มี middleware `sports.view` / `sports.manage` |
| frontend | `components/academy/sports/SportsEditionPanel.vue` + `useHouseAssignments.ts` + หน้า `house-assignments/index.vue` ใช้ edition แล้ว |
| tests | `php artisan test tests/Feature/Sports` → **33 passed / 118 assertions** รวมเคส "a second active edition is rejected by the database", "two editions in one year keep separate memberships", "activate demotes the previous edition and reprojects" |

**หมายเหตุความต่างจากสเปก 1 จุด:** `active_key` ถูกสร้างเป็น **VIRTUAL** generated column ไม่ใช่ STORED ตามที่ §9.1 เขียนไว้ — ผลลัพธ์เท่ากันเพราะ MySQL ทำ UNIQUE index บน virtual column ได้ และเทสต์ยืนยันว่า active ซ้อนกันถูก DB ปฏิเสธจริง จึงไม่ต้องแก้

---

## 10. S-S4 — ฐานคะแนนคณะสี (สเปกล็อก 2026-08-17)

### 10.0 ขอบเขต — ตัดสินแล้วโดยผู้ใช้ 2026-08-17

| ประเด็น | ผลตัดสิน |
|---|---|
| **S-S4 ครอบคลุมแค่ไหน** | ✅ **ฐานคะแนนก่อน** — `sports_disciplines` + `sports_score_entries` (event log) + `sports_house_standings` (aggregate) + กติกาอันดับร่วม + API ให้/หักคะแนนด้วยมือ · **ไม่มี `sports_matches` / `sports_match_results` ในก้อนนี้** ไปอยู่ S-S5 ที่เป็นเจ้าของการบันทึกผลการแข่ง (S-D3 bracket ยังเป็น ⏸ จึงยังไม่ต้องตัด) |
| **S-D8 *(ใหม่)* คะแนนเท่ากัน** | ✅ **อันดับร่วมได้คะแนนเท่ากัน แล้วข้ามอันดับถัดไป** — ที่ 1 ร่วม 2 คณะ → ได้ 9 ทั้งคู่ ไม่มีที่ 2 คณะถัดไปคือที่ 3 (standard competition ranking `1,1,3`) · ใช้กฎเดียวกันทั้งตอนแปลงอันดับเป็นคะแนน และตอนจัดอันดับตารางคะแนนรวม |
| **UI** | ไม่อยู่ใน S-S4 — หน้าจอทั้งหมดอยู่ที่ S-S6 · S-S4 เป็น backend ล้วน |

### 10.1 ตารางใหม่ 3 ตาราง — ทุกตาราง key ที่ `edition_id`

```
sports_disciplines                    ← รายการที่ให้คะแนนได้ 1 รายการ (ฟุตบอล, วิ่ง 100 ม., พาเหรด, กองเชียร์)
  id · edition_id FK cascade · academy_id FK cascade      ← academy_id เขียนจาก $edition->academy_id เท่านั้น ห้ามรับจาก request
  name varchar(150)
  type enum(team|individual|judged)   ← 3 รูปแบบคะแนนตาม §3
  scoring_table json nullable         ← {"1":9,"2":8,...} ใช้เมื่อ type != judged
  max_score decimal(8,2) nullable     ← ใช้เมื่อ type = judged (ค่าตั้งต้น 100)
  display_order unsigned smallint default 0
  timestamps
  UNIQUE [edition_id, name]  ชื่อ index `sd_edition_name_unique`
  index [edition_id, display_order]   ชื่อ `sd_edition_order_idx`

sports_score_entries                  ← event log: ทุกแต้มที่คณะสีได้ต้องมีแถวที่นี่ (§4 หลักการข้อ 2)
  id · edition_id FK cascade · academy_id FK cascade
  house_group_id FK academy_groups cascade
  discipline_id nullable FK sports_disciplines nullOnDelete   ← null ได้เมื่อเป็นคะแนนให้/หักด้วยมือล้วน
  source enum(placing|judged|manual)
  placing unsigned smallint nullable  ← ใช้เมื่อ source=placing (อันดับร่วมใส่เลขเดียวกันได้)
  points decimal(8,2)                 ← ติดลบได้ (หักคะแนน) · judged ใส่ทศนิยมได้
  note varchar(255) nullable
  ref_type varchar(60) nullable · ref_id unsigned bigint nullable
      ↑ เผื่อ S-S5 ผูกกลับไปที่ sports_match_results ได้โดยไม่ต้อง migration ใหม่
  awarded_by_user_id FK users
  voided_at nullable · voided_by_user_id nullable FK users
  timestamps
  index [edition_id, house_group_id]  ชื่อ `sse_edition_house_idx`
  index [edition_id, discipline_id]   ชื่อ `sse_edition_discipline_idx`

sports_house_standings                ← aggregate ล้วน สร้างใหม่จาก event log ได้เสมอ (§4 หลักการข้อ 3)
  id · edition_id FK cascade · house_group_id FK academy_groups cascade
  total_points decimal(10,2) default 0
  gold_count / silver_count / bronze_count unsigned int default 0
  rank unsigned smallint default 0    ← 0 = ยังไม่คำนวณ
  computed_at timestamp nullable
  timestamps
  UNIQUE [edition_id, house_group_id] ชื่อ `shs_edition_house_unique`
  index [edition_id, rank]            ชื่อ `shs_edition_rank_idx`
```

⚠️ **ชื่อ index ต้องสั้นและตั้งเอง** — MySQL จำกัดชื่อ index ที่ 64 ตัวอักษร และชื่อ auto ของ Laravel บนตารางชื่อยาวเคยทะลุมาแล้ว (บทเรียนเดียวกับ `hm_edition_student_unique` ใน §9)

### 10.2 กติกาคะแนน (S-D8) — เขียนไว้ที่เดียว ห้ามกระจาย

- **แปลงอันดับเป็นคะแนน**: `points = scoring_table[placing]` · อันดับร่วมใส่ `placing` เท่ากัน ⇒ ได้คะแนนเท่ากันโดยอัตโนมัติ · ถ้า `placing` เกินตารางให้ได้ 0 (ไม่ใช่ error — โรงเรียนให้คะแนนเฉพาะอันดับต้น ๆ)
- **จัดอันดับตารางคะแนนรวม**: เรียงตาม `total_points` มาก→น้อย · คะแนนเท่ากันได้ `rank` เท่ากัน แล้ว**ข้าม** rank ถัดไปตามจำนวนที่เสมอ (`1,1,3`)
- **ไม่มี tie-breaker อัตโนมัติ** — ไม่เอาจำนวนเหรียญมาตัดสินแทนคะแนน เพราะ §3 ไม่ได้กำหนดไว้ และการเดาเองจะกลายเป็นกฎที่ไม่มีใครสั่ง · `gold/silver/bronze_count` เก็บไว้แสดงผลเท่านั้น
- **แก้คะแนนที่ลงผิด = void แล้วลงใหม่** ห้าม UPDATE ทับ `points` และห้าม DELETE — แถวที่ `voided_at` ไม่ null ต้องไม่ถูกนับใน standings

### 10.3 Service — ตัวเขียน standings มีตัวเดียว (แบบเดียวกับ `HouseMembershipProjector`)

| ไฟล์ใหม่ | หน้าที่ |
|---|---|
| `app/Services/Sports/SportsScoringService.php` | `pointsForPlacing(SportsDiscipline, int $placing): float` · `award(...)` สร้างแถว event log แล้วสั่ง rebuild · `void(SportsScoreEntry, User)` |
| `app/Services/Sports/SportsStandingsProjector.php` | `rebuild(SportsEdition): void` — ลบ standings เดิมของ edition นั้น แล้วสร้างใหม่จาก event log ที่ยังไม่ถูก void **โดยต้องมีแถวของทุกคณะสีใน `sports_edition_houses` แม้ได้ 0 แต้ม** (เหตุผลเดียวกับ §9.1) · จัดอันดับตามกติกา 10.2 · ทำใน `DB::transaction` |

### 10.4 API — ทุก route อยู่ใต้ `{academy}/sports-editions/{edition}` และใช้ permission เดิม

| method + path | permission |
|---|---|
| `GET  /disciplines` · `POST /disciplines` · `PUT /disciplines/{discipline}` · `DELETE /disciplines/{discipline}` | view / manage / manage / manage |
| `GET  /score-entries` (กรองด้วย house_group_id, discipline_id ได้) | `sports.view` |
| `POST /score-entries` (source=placing ส่ง discipline_id+placing · source=judged ส่ง discipline_id+points · source=manual ส่ง points+note) | `sports.manage` |
| `POST /score-entries/{entry}/void` | `sports.manage` |
| `GET  /standings` | `sports.view` |
| `POST /standings/rebuild` | `sports.manage` |

- ทุก route ต้อง scope ด้วย academy แบบเดียวกับ `HouseAssignmentController::scoped()` — edition ที่ไม่ใช่ของ academy นั้น = 404
- `house_group_id` ที่ส่งมาต้องอยู่ใน `sports_edition_houses` ของ edition นั้น มิฉะนั้น 422 (กฎเดียวกับ "preview rejects houses that are not in the edition" ของ S-S3e)

### 10.5 เกณฑ์จบงาน

- migration รันบน MySQL จริงได้ + `down()` คืนรูปเดิมได้ (ตารางใหม่ล้วน ไม่มีข้อมูลเดิมให้ระวัง)
- เทสต์ใหม่ต้องครอบ: อันดับร่วมได้คะแนนเท่ากันและข้ามอันดับ · standings มีแถวคณะที่ได้ 0 แต้ม · แถวที่ void แล้วไม่ถูกนับ · คะแนนติดลบ (หักคะแนน) ทำงาน · house นอก edition ถูกปฏิเสธ 422 · edition ข้าม academy ได้ 404 · route ถูกกันด้วย permission
- `./vendor/bin/pint --test` ผ่าน

---

## 11. S-S6a — หน้าจอคะแนนกีฬาสี (สเปกล็อก 2026-08-17)

### 11.0 ทำไมแยก S-S6a ออกมาทำก่อน S-S5

S-S4 ให้คะแนนคณะสีได้ครบทั้ง 3 ทาง (`placing` / `judged` / `manual`) และรวมเป็นตารางคะแนนได้แล้ว **แต่ยังไม่มีใครเห็น** — พิสูจน์ได้แค่ในเทสต์
ส่วน S-S5 (บันทึกผลการแข่งรายคู่) ยังตัด **S-D3 (bracket)** ไม่ได้ → รอไปเรื่อย ๆ

→ แยกหน้าจอเป็น 2 ก้อน: **S-S6a ทำได้เลยวันนี้** ด้วยของที่ S-S4 มีอยู่จริง ทำให้เอาไปลองกับครูได้ทันที
ส่วนตารางแข่ง/กรอกผลรายคู่ (S-S6b) ไปพร้อม S-S5 หลังตัด S-D3

**S-D5 (คะแนนโผล่ที่ไหนบ้าง) ยังเป็น ⏸** → หน้านี้อยู่ใต้ `admin/` และกันด้วย `sports.view` เท่านั้น ยังไม่เปิดให้นักเรียน/สาธารณะ

### 11.1 ของที่สร้าง

| ไฟล์ | หน้าที่ |
|---|---|
| `ui/composables/useSportsScoring.ts` | สัญญากับ API ของ S-S4 ทั้งชุด + `pointsForPlacing()` (มิเรอร์ของฝั่ง service ใช้แสดงตัวอย่างเท่านั้น) + `DEFAULT_SCORING_TABLES` ตามเกณฑ์ §3 (ทีม 9→2 · เดี่ยว 5→2 · judged เต็ม 100) |
| `ui/pages/academies/[name]/admin/sports-scores/index.vue` | หน้าหลัก: เลือกครั้งที่จัด → 4 แท็บ (ตารางคะแนน · รายการแข่ง · ให้คะแนน · ประวัติ) |
| `ui/components/academy/sports/SportsStandingsBoard.vue` | ตารางคะแนนคณะสี + เหรียญ + ปุ่มคำนวณใหม่ |
| `ui/components/academy/sports/SportsDisciplineManager.vue` | CRUD รายการแข่ง + ตัวแก้ตารางคะแนน (อันดับ→คะแนน) |
| `ui/components/academy/sports/SportsScoreEntryForm.vue` | ให้คะแนน 3 ที่มา + ตัวอย่างคะแนนก่อนบันทึก |
| `ui/components/academy/sports/SportsScoreLog.vue` | ประวัติคะแนนทุกแต้ม + ยกเลิก (void) |
| `ui/pages/academies/[name]/admin.vue` | เมนู "คะแนนกีฬาสี" (add-only 1 บล็อก) |

### 11.2 ข้อที่หน้าจอต้องไม่ทำผิด (มาจากกติกาโดเมนของ §10.2)

1. **อันดับต้องอ่านจาก `rank` ที่ API ส่งมา ห้ามใช้ index ของ array** — อันดับร่วมเป็น `1,1,3` การนับจาก index จะกลายเป็น `1,2,3` เงียบ ๆ
2. **ต้องแสดงทุกคณะสีของครั้งนั้นเสมอ รวมคณะที่ได้ 0 แต้ม** — `GET /standings` คืน `[]` จนกว่าจะมีการให้คะแนนหรือ rebuild ครั้งแรก ถ้าหน้าจอวาดจาก standings อย่างเดียวจะว่างเปล่าทั้งที่ครั้งนั้นมีคณะสีครบ → วาดจาก `sports_edition_houses` แล้วเติมคะแนนทีหลัง
3. **แก้คะแนน = void แล้วลงใหม่** — ไม่มีปุ่มแก้คะแนนในหน้าจอ และแถวที่ void แล้วต้องยังเห็นในประวัติ (ขีดฆ่า) ไม่ใช่หายไป
4. **โหมด `manual` ห้ามส่ง `discipline_id`** (API ตอบ 422 ทันทีถ้ามีคีย์นี้) และ **โหมด `placing` ห้ามส่ง `points`** (API คิดเองจาก scoring_table)
5. `points` / `total_points` มาเป็น **string** (decimal cast) → ต้องแปลงก่อนเทียบทุกครั้ง
6. โหมด `manual` บังคับกรอกหมายเหตุ — คะแนนที่ไม่ผูกกับรายการแข่งต้องอธิบายที่มาได้ ไม่งั้น event log อ่านไม่รู้เรื่อง

### 11.2b 🔴 บั๊กที่เจอระหว่างทำ — auto-import ของ Nuxt ทำให้ component หายไปเงียบ ๆ

เรพนี้ **ไม่ได้ตั้ง `components.pathPrefix: false`** ใน `nuxt.config.ts` → Nuxt จดทะเบียนชื่อ component ตาม *โฟลเดอร์ + ชื่อไฟล์*
`components/academy/sports/SportsEditionPanel.vue` → ชื่อจริงคือ **`AcademySportsEditionPanel`** (ยืนยันจาก `ui/.nuxt/components.d.ts`)

เขียน `<SportsEditionPanel />` เฉย ๆ Vue จะ render เป็น custom element เปล่า **ไม่มี error ใน console ไม่มี warning** — จอหายไปทั้งแผงโดยไม่มีอะไรฟ้อง
พิสูจน์ในเบราว์เซอร์จริงแล้ว 2026-08-17: DOM ออกมาเป็น `<sportsstandingsboard standings="[object Object]...">`

→ **ผลกระทบย้อนหลัง:** หน้า `admin/house-assignments/index.vue` ของ S-S3e โดนบั๊กนี้อยู่ **แผงเลือก "ครั้งที่จัด" ไม่เคยแสดงจริงบนหน้าจอเลย** ทั้งที่ §9.6 บันทึกไว้ว่า "frontend เสร็จ" (ตรวจจากการอ่านโค้ด ไม่ได้เปิดดูจริง)
→ แก้แล้วทั้ง 2 หน้าโดย **import component เองตรง ๆ** (ไม่พึ่ง auto-import) เพราะถ้าชื่อผิดจะพังตอน compile ให้เห็น ไม่ใช่หายเงียบ

### 11.3 ช่องว่างที่รู้ตัวแล้ว (ยังไม่แก้ในก้อนนี้)

- `GET /score-entries` **ไม่มีการแบ่งหน้า** คืนทุกแถวของครั้งนั้น — งานจริงหนึ่งครั้งอาจมีหลายร้อยแถว หน้าจอจึงกรองฝั่ง client ไปก่อน ถ้าเริ่มช้าค่อยเพิ่ม pagination ที่ API
- `entries()` ไม่ eager-load `houseGroup`/`awardedBy` → หน้าจอแปลงชื่อคณะสีเองจากรายชื่อกลุ่ม และยัง**ไม่แสดงว่าใครเป็นคนให้คะแนน** (ต้องเพิ่ม `with('awardedBy')` ฝั่ง API ก่อนถึงจะแสดงได้)

### 11.4 ยืนยันกับ API จริงแล้ว 2026-08-17

เดินครบทั้งเส้นบนเครื่อง dev ด้วยบัญชีเจ้าของโรงเรียน (ข้อมูลทดสอบขึ้นต้นด้วย `ทดสอบ-` และลบออกหมดแล้ว — ตรวจซ้ำว่า `academy_groups` กลับมา 35 แถวเท่าเดิม):

| ตรวจ | ผล |
|---|---|
| แผง "งานกีฬาสี" ที่หน้า house-assignments | ✅ แสดงจริงหลังแก้ auto-import (ก่อนแก้ไม่มีบล็อกนี้เลย) |
| สร้างครั้งที่จัด + เลือกคณะสีผ่านฟอร์ม | ✅ |
| ตารางคะแนนตอนยังไม่มีคะแนน | ✅ แสดงครบทุกคณะที่ 0 อันดับ `—` ทั้งที่ API คืน `[]` |
| สร้างรายการแข่ง 3 ประเภท | ✅ ค่าตั้งต้น 9→2 / 5→2 / เต็ม 100 ถูกทุกอัน |
| อันดับ 1 ร่วม 2 คณะ | ✅ ได้ +9 ทั้งคู่ · ตัวอย่างคะแนนก่อนกดตรงกับที่ API คิด |
| หักคะแนนด้วยมือ −5 | ✅ บังคับกรอกหมายเหตุก่อนถึงกดได้ |
| void คะแนนกรรมการ 87.5 | ✅ ตัวนับเป็น "นับ 3 / ยกเลิก 1" · แถวยังอยู่ในประวัติ |
| อันดับหลัง void | ✅ **1, 1, 3** (แดง 9 · น้ำเงิน 9 · เขียว −5) |
| จอ 375px | ✅ ไม่มี horizontal scroll · ปุ่มของหน้านี้ ≥44px ครบ |

⚠️ **กับดักของ dev server ที่เสียเวลาไป 20 นาที** — Nuxt/Vite เด้ง `504 (Outdated Optimize Dep)` หลังเพิ่มไฟล์ใหม่ ทำให้ทุกหน้าใต้ `academies/**` กลายเป็น "500 Page Not Found" ทั้งที่โค้ดไม่ผิด · แก้ด้วย `rm -rf ui/node_modules/.vite` แล้วรีสตาร์ท dev server

---

## 12. S-D3 + S-S5 — ตารางแข่งและผลการแข่งขัน (สเปกล็อก 2026-08-17)

### 12.0 ข้อตัดสิน S-D3 (ผู้ใช้ตัดสิน 2026-08-17)

| ประเด็น | ผลตัดสิน |
|---|---|
| **ระบบช่วยจัดตารางแข่งแค่ไหน** | ✅ **ครบทุกรูปแบบรวมกรีฑา** — แพ้คัดออก (knockout) · พบกันหมด (round-robin) · ฮีตจับเวลา (heats) · และ `none` สำหรับรายการที่กรรมการให้คะแนนล้วน |
| **อันดับสุดท้ายมาจากไหน** | ✅ **ระบบเสนอ ครูกดยืนยัน** — ตัวเสนอคำนวณจากผลแมตช์ แต่ไม่มีแต้มไหนลง event log จนกว่าคนจะกดยืนยัน (กันเคสจริงที่ผลไม่ตรงสูตร: ทีมถอนตัว ปรับแพ้ ตัดสินใหม่) |
| **QR เช็คชื่อนักกีฬา** | ✅ **เก็บช่อง `activity_session_id` แบบ nullable ไว้ตั้งแต่แรก แต่ยังไม่ทำหน้าจอ** → ไม่ต้องบังคับให้ edition ผูกกับ `school_events` ตอนนี้ และเติมทีหลังได้โดยไม่ต้อง migration ใหม่ |

**เหตุผลที่ต้องแยกตัวเสนอออกจากตัวลงคะแนนให้ชัด** — §10.2 ล็อกไว้แล้วว่าแก้คะแนนคือ void แล้วลงใหม่ ห้าม UPDATE ทับ ถ้าให้ผลแมตช์ยิงเข้า event log อัตโนมัติ ทุกครั้งที่ครูแก้สกอร์ที่พิมพ์ผิดจะเกิดแถวคะแนนใหม่ทับกันเป็นชั้น ๆ โดยไม่มีใครสั่ง

### 12.1 หลักการที่ยึด

1. **แมตช์ไม่ใช่แหล่งของคะแนน** — แหล่งของคะแนนยังเป็น `sports_score_entries` เหมือนเดิม แมตช์เป็นแค่ที่มาที่อ้างกลับได้ผ่าน `ref_type`/`ref_id` ที่เตรียมไว้แล้วใน §10.1
2. **1 แมตช์มีผู้เข้าแข่งกี่คณะก็ได้** ไม่ใช่ 2 เสมอ — ฮีตกรีฑามี 4–8 ลู่ในแมตช์เดียว ถ้าออกแบบเป็น `house_a` / `house_b` จะรองรับกรีฑาไม่ได้เลยและต้องรื้อ
3. **เวลาเก็บเป็นจำนวนเต็มมิลลิวินาที** ไม่ใช่ float วินาที — กรีฑาตัดสินกันที่ 1/100 วินาที และ float ทำให้ 12.34 กับ 12.34 ไม่เท่ากันได้ (บทเรียนเดียวกับที่ต้อง `round()` ใน `SportsStandingsProjector`)
4. **สถานะรายคน `dq/dns/dnf` ต้องมีตั้งแต่แรก** — ผิดกติกา/ไม่มาแข่ง/ไม่จบการแข่ง เป็นเรื่องปกติของงานกีฬา ถ้าไม่มีจะถูกยัดเป็น "เวลา 0" แล้วกลายเป็นที่ 1

### 12.2 schema ใหม่ 3 ตาราง + 1 คอลัมน์

```
sports_disciplines (มีอยู่แล้ว)
  + format enum(none|knockout|round_robin|heats) default 'none'   ← ใหม่ · บอกว่าใช้ตัวสร้างคู่/ตัวเสนออันดับตัวไหน

sports_matches                        ← 1 คู่ / 1 ฮีต / 1 รอบ
  id · edition_id FK cascade · academy_id FK cascade   ← academy_id เขียนจาก $edition เท่านั้น
  discipline_id FK sports_disciplines cascade
  activity_session_id nullable FK activity_sessions nullOnDelete   ← เก็บช่องไว้ ยังไม่ใช้ (S-D3)
  round_label varchar(60) nullable      ← 'รอบแรก' 'รอบรองชนะเลิศ' 'ชิงชนะเลิศ' 'ฮีต 1'
  round_order unsigned smallint default 0 · match_number unsigned smallint default 1
  scheduled_at datetime nullable · location varchar(150) nullable
  status enum(scheduled|in_progress|finished|cancelled) default 'scheduled'
  winner_house_group_id nullable FK academy_groups nullOnDelete
  next_match_id nullable FK sports_matches nullOnDelete   ← สายแพ้คัดออก: ผู้ชนะไปคู่ไหนต่อ
  next_match_slot unsigned tinyint nullable               ← ไปเป็นลู่/ฝั่งที่เท่าไหร่ของคู่นั้น
  note varchar(255) nullable · timestamps
  index [edition_id, discipline_id, round_order]  ชื่อ sm_ed_disc_round_idx

sports_match_participants             ← ใครลงแข่งในแมตช์นั้น (n คณะ ไม่ใช่ 2)
  id · match_id FK cascade · house_group_id FK academy_groups cascade
  slot unsigned tinyint default 1       ← ลู่/ฝั่งที่เท่าไหร่
  score decimal(8,2) nullable           ← สกอร์ (ฟุตบอล 3-1)
  time_ms unsigned int nullable         ← เวลา หน่วยมิลลิวินาที (ดู 12.1 ข้อ 3)
  placing unsigned smallint nullable    ← อันดับ "ในแมตช์นี้" อันดับร่วมใส่เลขเดียวกันได้
  status enum(ok|dq|dns|dnf) default 'ok'
  timestamps
  UNIQUE [match_id, house_group_id]  ชื่อ smp_match_house_unique
  index [match_id, slot]             ชื่อ smp_match_slot_idx

sports_discipline_results             ← อันดับสุดท้ายของรายการ ที่ "ยืนยันแล้ว" เท่านั้น
  id · edition_id FK cascade · discipline_id FK cascade
  house_group_id FK academy_groups cascade
  placing unsigned smallint             ← อันดับร่วมใส่เลขเดียวกันได้ (S-D8)
  source enum(suggested|manual)         ← ยืนยันตามที่ระบบเสนอ หรือครูแก้เอง
  score_entry_id nullable FK sports_score_entries nullOnDelete  ← แต้มที่ลงจากอันดับนี้
  confirmed_at · confirmed_by_user_id FK users · timestamps
  UNIQUE [discipline_id, house_group_id]  ชื่อ sdr_disc_house_unique
```

⚠️ ชื่อ index ต้องสั้นและตั้งเอง (ข้อจำกัด 64 ตัวอักษรของ MySQL — บทเรียนเดิมจาก §9/§10)

### 12.3 บันทึกผลรายแมตช์ (S-S5a)

`PUT /matches/{match}/result` รับ `participants[]` = `{house_group_id, score?, time_ms?, placing?, status}` แล้ว:
- เขียนทับผลของแมตช์นั้นได้ตามปกติ (**แมตช์ไม่ใช่ event log** — ที่ห้ามแก้ทับคือ `sports_score_entries` เท่านั้น)
- ตั้ง `status='finished'` + `winner_house_group_id` = คนที่ `placing=1` (ถ้ามีคนเดียว)
- ถ้า `next_match_id` ไม่ null → ใส่ผู้ชนะลงเป็นผู้เข้าแข่งของแมตช์ถัดไปที่ `next_match_slot` (upsert ไม่ใช่ insert ซ้ำ)
- ⛔ **ห้ามแตะ `sports_score_entries` ในขั้นนี้เด็ดขาด**

### 12.4 ตัวสร้างคู่อัตโนมัติ (S-S5b) — `app/Services/Sports/SportsFixtureGenerator.php`

`POST /disciplines/{discipline}/generate-fixtures` · body: `{format, house_group_ids[], options}`
- **ต้องปฏิเสธ 422 ถ้ารายการนั้นมีแมตช์ที่ `finished` แล้ว** (ไม่งั้นการกดปุ่มซ้ำจะล้างผลที่บันทึกไปแล้วทิ้ง) · มีแค่แมตช์ `scheduled` ให้ล้างแล้วสร้างใหม่ได้
- `round_robin` — circle method · n คณะ → n(n−1)/2 คู่ · n คี่ใส่ bye
- `knockout` — เติม bye ให้ครบกำลังของ 2 · สร้างทุกคู่ล่วงหน้าพร้อมผูก `next_match_id`/`next_match_slot` · option `third_place: true` สร้างคู่ชิงที่ 3
- `heats` — option `lanes_per_heat` (ค่าตั้งต้น 8) · แบ่งคณะเป็นฮีต + สร้างรอบชิง 1 แมตช์ที่ `round_order` สูงสุด
- `none` — 422 บอกว่ารายการรูปแบบนี้ไม่มีตารางแข่ง

### 12.5 ตัวเสนออันดับ + ยืนยัน (S-S5c) — `SportsPlacingSuggester.php`

`GET /disciplines/{discipline}/suggested-placings` → คืน `{house_group_id, placing, reason}` **โดยไม่เขียนอะไรลง DB เลย**

| format | วิธีเสนอ |
|---|---|
| `knockout` | ผู้ชนะรอบชิง = 1 · ผู้แพ้รอบชิง = 2 · ผู้ชนะคู่ชิงที่ 3 = 3 (ถ้าไม่มีคู่ชิงที่ 3 → ผู้แพ้รอบรองฯ ได้ที่ 3 ร่วมทั้งคู่) |
| `round_robin` | ชนะ 3 เสมอ 1 แพ้ 0 → เรียงแต้ม → ผลต่างสกอร์ → สกอร์ได้ · **เท่ากันทุกตัวชี้ขาด = อันดับร่วม ห้ามเดา** |
| `heats` | เรียง `time_ms` ของแมตช์ `round_order` สูงสุด จากน้อยไปมาก · `dq/dns/dnf` ไม่ได้อันดับ |
| `none` | ไม่เสนอ (คืน []) ครูกรอกเอง |

`POST /disciplines/{discipline}/confirm-placings` · body: `{placings: [{house_group_id, placing}], source}` →
1. เขียน `sports_discipline_results`
2. เรียก `SportsScoringService::award()` ให้ทุกคณะ (source=`placing`) พร้อม `ref_type='sports_discipline_results'` + `ref_id`
3. เก็บ `score_entry_id` กลับลงแถวผลลัพธ์
4. **ยืนยันซ้ำ = void แถวคะแนนเดิมทั้งชุดก่อน แล้วลงใหม่** ห้าม UPDATE ทับ (§10.2) และ `sports_discipline_results` ต้องบันทึกว่าเป็นการยืนยันรอบใหม่

### 12.6 เกณฑ์จบงานของแต่ละ shard

**S-S5a** — migration รันบน MySQL จริง + `down()` คืนรูปเดิมได้ · เทสต์: แมตช์ 1 ใบมีผู้เข้าแข่ง 6 คณะได้ · `time_ms` เก็บ/อ่านกลับตรง · คณะนอก edition ถูกปฏิเสธ 422 · แมตช์ข้าม academy ได้ 404 · บันทึกผลแล้ว **ไม่มีแถวใน `sports_score_entries` เพิ่มแม้แต่แถวเดียว**
**S-S5b** — เทสต์: round-robin 4 คณะได้ 6 คู่ · knockout 4 คณะได้ 3 คู่ (รองฯ 2 + ชิง 1) และผูก `next_match_id` ถูก · knockout 5 คณะได้ bye ที่ถูกต้อง · heats 12 คณะ 8 ลู่ = 2 ฮีต + ชิง · **กด generate ซ้ำตอนมีแมตช์ finished แล้ว = 422 ไม่ล้างผล**
**S-S5c** — เทสต์: เสนออันดับถูกทั้ง 3 format · เสมอกันหมดใน round-robin ได้อันดับร่วม · `dq` ไม่ได้อันดับ · ยืนยันแล้วคะแนนเข้าตารางถูกตามตาราง scoring · **ยืนยันซ้ำแล้วคะแนนไม่บวกซ้ำ** (แถวเดิมถูก void) · `GET suggested-placings` ไม่เขียน DB
ทุก shard: `./vendor/bin/pint --test` ผ่าน · ทุก route มี `academy.permission:sports.view|manage` ตาม §10.4
