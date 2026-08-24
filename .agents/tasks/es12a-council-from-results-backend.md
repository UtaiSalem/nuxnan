# E-S12a — backend: ตั้งคณะกรรมการสภานักเรียนจากผลเลือกตั้ง

> เขียนโดย claude 2026-08-24 · เมนู #25 · shard a ของ E-S12 · **งานใน `api/` เท่านั้น**
> อ่านประกอบ: `.agents/school-admin/25-elections.md` **§14** (สเปกเต็ม) · **§14.1** (ข้อตัดสินของผู้ใช้ ห้ามเปลี่ยน) ·
> **§14.3** (กับดักสามข้อ — อ่านก่อนเขียนโค้ด) · **§7.2** (บทเรียนเรื่องเทสต์ที่ไม่ยิงผ่าน route จริง)

## เป้าหมาย

ผลเลือกตั้งที่ประกาศแล้ว ต้องกลายเป็น **กลุ่มจริงในระบบพร้อมสมาชิก** ไม่ใช่ตัวเลขที่ไม่ไปไหนต่อ

---

## 1. เพิ่ม type `student_council` ใน `app/Constants/AcademyGroupTypes.php`

ค่าที่ล็อกไว้แล้ว **ห้ามคิดเอง** (shard b เขียนฝั่ง TS ให้ตรงกันอยู่):

```
key: student_council · label: 'สภานักเรียน' · label_en: 'Student Council'
icon: 'heroicons:megaphone' · color: 'pink' · order: 10
```

ไฟล์นี้มีคอมเมนต์เขียนไว้อยู่แล้วว่า *"Mirror this when changing ui/constants/academyGroupTypes.ts"* —
**ห้ามแก้ type อื่นทั้ง 9 ตัว** แตะเฉพาะการเพิ่มตัวที่ 10

## 2. Endpoint ใหม่

```
POST /api/academies/{academy}/elections/{election}/council     middleware: elections.manage
body: { "name": "…" }   // optional — ไม่ส่งให้ derive จาก election.title
```

เพิ่มใน `routes/learn/election.php` · **ห้ามแตะ middleware ของ route อื่น** และ
**ห้ามแตะ throttle limiter ทั้ง 5 เส้น** ที่เพิ่งจัดไปใน E-S11a

### การ์ด 3 ชั้น — ต้องตรวจตามลำดับนี้ ทุกข้อความเป็นภาษาไทย

| ลำดับ | เงื่อนไข | ตอบกลับ |
|---|---|---|
| 1 | `election.published_at` ต้องไม่ว่าง | 422 "ยังประกาศผลไม่เสร็จ ตั้งคณะกรรมการไม่ได้" |
| 2 | นับ `election_results` ที่ `is_winner = true` ต้องได้ **1 แถวเท่านั้น** | > 1 → 422 **พร้อมชื่อพรรคที่เสมอกันและคะแนน** · = 0 → 422 |
| 3 | ต้องไม่มี `academy_groups` ที่ `settings->election_id` = election นี้ | 422 พร้อม `group_id` + `group_name` ของเดิม |

**🔴 การ์ดข้อ 2 ไม่ใช่ความระมัดระวังลอย ๆ** — `ElectionResultService::closeAndCount()` ตั้ง
`is_winner = ((int) $group->votes === $top)` → **เสมอที่หนึ่งกี่พรรคก็ติดธงทั้งหมด**
ผู้ใช้ตัดสินแล้วว่า **ระบบต้องปฏิเสธและให้ กกต. ตัดสินเอง ห้ามเลือกให้** (§14.1 G3)

## 3. สิ่งที่สร้างเมื่อผ่านการ์ดครบ (ต้องอยู่ใน transaction เดียว)

- **`AcademyGroup`** — `type = 'student_council'` · `academy_id` ของ election ·
  `settings = ['election_id' => …, 'party_id' => …, 'published_at' => …]`
  **`settings` คือตัวผูกความสัมพันธ์และเป็นตัวที่การ์ดข้อ 3 ใช้เช็ค** — คอลัมน์นี้ cast เป็น `array` อยู่แล้ว
  ใน `AcademyGroup::$casts` → **ห้ามเพิ่มคอลัมน์ใหม่ ห้ามเขียน migration ใด ๆ ในงานนี้**
- **`AcademyGroupPermission` ครบทุก key** — ดูข้อ 4
- **`academy_group_members`** หนึ่งแถวต่อสมาชิกพรรคที่ชนะ (`election_party_members` ทั้ง 5 บทบาท)
  · `role` = **ค่าเดิมจาก `election_party_members.role` ตรง ๆ** (leader/deputy/secretary/treasurer/member)
  **ห้ามยุบเป็น 'member' ทั้งหมด** ไม่งั้นจะไม่รู้ว่าใครเป็นประธาน
  · `status = 2` (2 = approved) · `invited_by` = ผู้กด
- **`academy_group_admins`** — `leader` ของพรรคหนึ่งแถว เพื่อให้ประธานจัดการกลุ่มตัวเองได้
- **audit log** — เพิ่ม `MemberActivityLog::ACTION_ELECTION_COUNCIL_CREATE` ตามรูปแบบเดิมของโดเมนนี้
  (ดูตัวอย่างที่ `ElectionStationService::log()` ทำอยู่)

**หมายเหตุที่ตัดสินแล้ว:** `election_party_members.position_label` **ไม่ถูกยกไป** เพราะ
`academy_group_members` ไม่มีคอลัมน์รองรับ · **ห้ามเพิ่มคอลัมน์เพื่อรองรับ** — เป็นหนี้ที่รู้ตัวและยอมรับแล้ว (§14.3 T3)

## 4. 🔴 กับดักที่จะพังเงียบที่สุด — permission ของกลุ่ม

`AcademyGroupController::store()` **ไม่ได้แค่ `create()`** แต่ยัง seed `AcademyGroupPermission`
ให้ครบทุก key จาก `AcademyGroupPermissions::PERMISSIONS` ตามค่า `default` ของแต่ละตัว

→ ถ้า service ของงานนี้สร้างกลุ่มเองตรง ๆ จะได้ **สภานักเรียนที่ไม่มีสิทธิ์ติดมาเลยสักตัว**
หน้าจัดการกลุ่มจะว่างเปล่าโดยไม่มีอะไรฟ้อง

→ **ดึงตรรกะ seed ออกมาเป็นจุดเดียวที่ใช้ร่วมกัน** (service/method ที่ทั้ง `store()` เดิมและ service ใหม่เรียก)
**ห้าม copy-paste ลูป seed ไปไว้สองที่** — ถ้าวันหนึ่งมี permission key เพิ่ม แล้วสองที่ไม่ตรงกัน
จะกลายเป็นบั๊กที่หาไม่เจอ · `store()` เดิม **ต้องมีพฤติกรรมเหมือนเดิมเป๊ะ**

---

## เทสต์ที่ต้องมี (ระดับ HTTP ทั้งหมด ยิงผ่าน route จริงด้วย `actingAs($actor,'api')`)

1. **เส้นทางสำเร็จ** — election ที่ประกาศผลแล้วและมีผู้ชนะเดียว → 201/200
   แล้วยืนยันว่ากลุ่มถูกสร้างด้วย `type = 'student_council'` และ `settings.election_id` ตรง
2. **สมาชิกครบและบทบาทไม่หาย** — จำนวนแถวใน `academy_group_members` เท่ากับจำนวนสมาชิกพรรค
   และแถวของ `leader` ยังมี `role = 'leader'` (ไม่ถูกยุบเป็น member) · `status = 2` ทุกแถว
3. **`leader` อยู่ใน `academy_group_admins`**
4. **🔴 permission ครบ** — จำนวนแถว `academy_group_permissions` ของกลุ่มใหม่
   เท่ากับจำนวน key ใน `AcademyGroupPermissions::PERMISSIONS`
   → **ถ้าเทสต์นี้ไม่มี ข้อ 4 ข้างบนจะหลุดโดยไม่มีใครรู้**
5. **การ์ด 1** — election ที่ยังไม่ประกาศผล → 422
6. **การ์ด 2** — สร้างสองพรรคคะแนนเท่ากันจนได้ `is_winner` สองแถว → 422 **และ response ต้องมีชื่อพรรคที่เสมอ**
7. **การ์ด 3** — เรียกซ้ำครั้งที่สอง → 422 และต้องได้ `group_id` ของกลุ่มเดิมกลับมา
   **และต้องยืนยันว่าไม่มีกลุ่มที่สองเกิดขึ้นในฐาน**
8. **สิทธิ์** — คนที่มีแค่ `elections.view` เรียกแล้วต้องได้ 403

---

## เกณฑ์ที่ claude จะใช้ตรวจ (ตรวจเอง ไม่เชื่อรายงาน)

1. `git diff --stat` + อ่าน diff ทุกไฟล์ ดูเลข deletion
2. ตรวจว่า `AcademyGroupController::store()` **ยังทำงานเหมือนเดิม** หลังดึงตรรกะ seed ออก
3. `route:list --path=elections` — route ใหม่มี guard `elections.manage` และ **5 เส้นเดิมยังมี limiter ชื่อเดิม**
4. `./vendor/bin/pint --test` ผ่าน
5. `php artisan test --filter Election` — **ฐาน 147 เทสต์ / 449 assertions ห้ามลดลง** และต้องเพิ่มอย่างน้อย 8 เคส

## ขอบเขต — ห้ามเกินนี้

- **ห้ามแตะ `ui/`** ทั้งหมด (shard b ทำขนานกันอยู่ที่ `academyGroupTypes.ts` · `ElectionResultsTab.vue` · `useElections.ts`)
- **ห้ามเขียน migration และห้ามแตะ schema** — `settings` รองรับการผูกได้แล้ว
- **ห้ามรัน `php artisan migrate` ทุกรูปแบบ** โดยเฉพาะ `migrate:fresh` (DB มีนักเรียนจริง 2,931 คน)
- **ห้ามแก้ `closeAndCount()`** — ถ้าคิดว่าตรรกะ `is_winner` ควรเปลี่ยน **ให้รายงาน อย่าแก้เอง**
  (มันเป็นพฤติกรรมที่ผู้ใช้ตัดสินให้ปฏิเสธที่ชั้น API แทน)
- ถ้าเจออย่างอื่นที่คิดว่าเป็นบั๊ก **ให้รายงาน อย่าแก้เอง**
