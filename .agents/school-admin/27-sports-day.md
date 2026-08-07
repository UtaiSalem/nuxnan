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
| **S-D3** | bracket | ⏸ ยังไม่ถึงคิว (S-S4 ขึ้นไป) |
| **S-D4** | นักกีฬาสมัครเอง | ⏸ ยังไม่ถึงคิว |
| **S-D5** | คะแนนโผล่ที่ไหน | ⏸ ยังไม่ถึงคิว |
| **S-D6** | ปีถัดไป | ✅ **แบ่งใหม่ทุกครั้งที่จัด** (เดิมเขียนว่า "ทุกปี" — ขยายความ 2026-08-08 เพราะจัดได้หลายครั้ง/ปี) → สมาชิกภาพคณะสี**ผูกกับ `sports_editions` ไม่ใช่ `academic_year_id`** · ปีการศึกษาเป็นคุณสมบัติของ edition |
| **S-D7** *(ใหม่)* | เกณฑ์กระจายของตัวสุ่ม | ✅ ค่าเริ่มต้น = **คละทุกห้องเท่า ๆ กัน + สมดุลชาย/หญิง** · และต้องมีตัวเลือก **สุ่มล้วนทั้งโรงเรียน** ให้เลือกตอนรัน |

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
| **S-S3e** *(ใหม่ 2026-08-08 — แทรกก่อน S-S4)* | **หน่วย "ครั้งที่จัด"** — ตาราง `sports_editions` + `sports_edition_houses` · ย้ายคีย์ของ `house_memberships`/`house_assignment_batches` จาก `academic_year_id` → `edition_id` · projector ฉายจาก edition ที่ `active` เท่านั้น (§9) | S-S3b | ⚪ |
| **S-S4** | schema กีฬาสี (§4) + ให้คะแนนแก่คณะสีผ่าน event log + จัดการคะแนนเท่ากัน — **ทุกตาราง key ที่ `edition_id`** | **S-S3e** | ⚪ |
| **S-S5** | บันทึกผลการแข่ง (อันดับ → คะแนนตามตาราง) + คะแนนกรรมการตามเกณฑ์ย่อย (§3) | S-S4 | ⚪ |
| **S-S6** | หน้าจอ: ตารางคะแนนคณะสี · ตารางแข่ง · กรอกผล · สรุปเหรียญ | S-S5 | ⚪ |
| **S-S7** | อัลบั้มภาพผูกกับงาน (ต้องเพิ่ม owner ให้ `albums` หรือทำตารางใหม่ — ดู §1.3) | S-S6 | ⚪ |

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
