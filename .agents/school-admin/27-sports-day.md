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

```
SchoolEvent (event_type='sports')          ← งานกีฬาสีทั้งงาน 1 ครั้ง/ปี
  └─ sports_houses  →  AcademyGroup(type='house')   ← คณะสี (ชื่อ/สี/ไอคอน อยู่ใน settings)
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

## 5. ต้องตัดสินก่อนล็อกแผน (ยังไม่ได้ถาม)

| # | ประเด็น | ทางเลือก / ทำไมสำคัญ |
|---|---|---|
| **S-D1** | **แบ่งนักเรียน 2,931 คนเข้าคณะสียังไง** | สุ่มคละชั้น · ตามห้องเรียน (ทั้งห้องอยู่สีเดียว) · ตามเลขที่/นามสกุล · นำเข้าจากไฟล์ที่โรงเรียนแบ่งไว้แล้ว — **ข้อนี้เปลี่ยนงานมากที่สุด** และถ้าปีก่อนแบ่งไว้แล้วต้องรู้ว่าจะสืบทอดยังไงตอนเลื่อนชั้น |
| **S-D2** | กี่คณะสี | 4 / 5 / 6 — มีผลกับตารางแข่งและตัวคูณคะแนน |
| **S-D3** | ต้องมีสายการแข่งขัน (bracket) ไหม | ถ้าต้องมี = งานใหญ่ขึ้นมาก (`sports_matches` ต้องรู้จักรอบ/คู่/ผู้ชนะไปต่อ) · ถ้าแค่บันทึกอันดับสุดท้ายของแต่ละรายการ = เล็กลงมาก **แนะนำเริ่มจากบันทึกอันดับก่อน** |
| **S-D4** | นักกีฬาลงชื่อสมัครในระบบ หรือครูกรอกผลอย่างเดียว | ถ้ามีสมัคร → ใช้ `ActivityEnrollment` ต่อได้ · ผูกกับเมนู #26 |
| **S-D5** | คะแนนคณะสีโผล่ที่ไหนบ้าง | เฉพาะหน้ากีฬาสี · หรือขึ้นหน้าแรกโรงเรียน/ฟีดด้วย |
| **S-D6** | ปีถัดไปทำยังไง | คณะสีอยู่ถาวรข้ามปี (นักเรียนสังกัดสีเดิมจนจบ) หรือแบ่งใหม่ทุกปี — กระทบว่า `academy_groups` ผูกกับ `academic_year` หรือไม่ |

---

## 6. Implementation Tasks (ร่าง — รอ §5)

| Step | Title | Depends | Status |
|---|---|---|---|
| **S-S1** | ลงทะเบียน type `house` ใน **`app/Constants/AcademyGroupTypes.php` และ `ui/constants/academyGroupTypes.ts` (mirror ทั้งคู่)** + สี/ไอคอนใน `settings` → ปลดล็อก CRUD/สมาชิก/ประธานสี/ฟีดกลุ่มที่มีอยู่แล้ว | — | ⚪ |
| **S-S2** | **แก้ `houseLeaderboard()` ให้เลิกอ่าน `SUM(users.pp)`** — ชี้ไป event log ของกีฬาสีแทน · ปิดช่องภาระจ่ายเงินจริง | S-S1 | ⚪ |
| **S-S3** | เครื่องมือแบ่งนักเรียนเข้าคณะสี (ตาม S-D1) + preview ก่อน commit ตามรูปของ `RolloverBatch` | S-S1, S-D1 | ⚪ |
| **S-S4** | schema กีฬาสี (§4) + ให้คะแนนแก่คณะสีผ่าน event log + จัดการคะแนนเท่ากัน | S-S2 | ⚪ |
| **S-S5** | บันทึกผลการแข่ง (อันดับ → คะแนนตามตาราง) + คะแนนกรรมการตามเกณฑ์ย่อย (§3) | S-S4 | ⚪ |
| **S-S6** | หน้าจอ: ตารางคะแนนคณะสี · ตารางแข่ง · กรอกผล · สรุปเหรียญ | S-S5 | ⚪ |
| **S-S7** | อัลบั้มภาพผูกกับงาน (ต้องเพิ่ม owner ให้ `albums` หรือทำตารางใหม่ — ดู §1.3) | S-S6 | ⚪ |

---

## 7. Review Log

- **2026-07-31 — สแกน + เขียนสเปกร่าง** — พบว่าเมนูนี้มี house leaderboard ต่อสายครบตั้งแต่ route ถึง UI แต่ **ตายทั้งเส้นด้วยเหตุผล 2 ข้อที่ไม่เกี่ยวกัน**: `type='house'` ไม่ได้อยู่ใน registry (สร้างไม่ได้) และคะแนนคิดจาก `SUM(users.pp)` ซึ่งเป็นเงินจริง (ใช้ไม่ได้แม้สร้างได้) · ยืนยันจาก DB: `academy_groups` ไม่มีแถว `house` และไม่มีแถว `classroom` เลย → **เส้นทางให้คะแนนแบบกลุ่มทั้งเส้นไม่เคยถูกรันจริง** ต้องนับ `xp_events`/`ClassroomPointsService` เป็น "เขียนแล้วแต่ยังไม่พิสูจน์" เหมือนเมนู #26 · ยังไม่ล็อกแผนเพราะ §5 มี 6 ข้อค้าง โดยเฉพาะ S-D1 (วิธีแบ่งนักเรียน) ที่เปลี่ยนงานมากที่สุด
