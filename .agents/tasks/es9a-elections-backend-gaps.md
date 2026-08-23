# E-S9a — อุดช่องว่าง backend ก่อนทำหน้าแอดมินการเลือกตั้ง

> เขียนโดย claude 2026-08-23 · เมนู #25 การเลือกตั้งสภานักเรียน · shard แรกของ E-S9
> อ่านประกอบ: `.agents/school-admin/25-elections.md` **§11** (สเปกหน้าแอดมินเต็ม) · **§11.3** (ช่องว่าง G1–G3) · **§7.2** (บทเรียน route binding)

## บริบท

E-S8 ปิดแล้ว หน้าหน่วยเลือกตั้งใช้งานได้จริงครบเส้น (ยิง HTTP ผ่านหมด `cast` ลงบัตรได้จริง)
งานถัดไปคือหน้าแอดมิน (E-S9) ซึ่งจะมี 6 แท็บ — แต่ **มีสองจุดที่ backend ยังทำไม่ได้เลย** ต้องอุดก่อนเริ่มงานหน้าจอ
ไม่งั้นคนทำ frontend จะไปนั่งไล่หาสาเหตุผิดที่ (เหมือนกรณี §7.2)

งานนี้เป็น **backend ล้วน** ไม่ต้องแตะ `ui/` เลย

---

## ส่วนที่ 1 — G1: ไม่มี `GET /{election}/stations`

ตอนนี้ `routes/learn/election.php` มี `store` / `update` / `destroy` ของหน่วยเลือกตั้ง **แต่ไม่มี index**
→ แท็บ "หน่วย" ของหน้าแอดมินลิสต์หน่วยไม่ได้ และไม่มีที่ไหนแจกลิงก์ `?station={id}` ให้กรรมการ
ซึ่งเป็นทางเข้าเดียวของหน้าหน่วยเลือกตั้ง

**ต้องทำ:**
- เพิ่ม `Route::get('/{election}/stations', [ElectionStationController::class, 'index'])` guard `academy.permission:elections.manage`
  (วางให้อยู่กลุ่มเดียวกับ route หน่วยอื่น ๆ)
- `index()` คืนรายการหน่วยของการเลือกตั้งนั้น เรียงตาม `name` โดยแต่ละแถวมี:
  `id` · `name` · `location` · `is_open` (bool) · `opened_at` · `closed_at` · `issued_count` · `cast_count`
- ตัวนับสองตัวต้องมาจาก `withCount` ไม่ใช่ลูปนับทีละหน่วย (โรงเรียนนี้จะมีหลายหน่วยในวันจริง)
- 🔴 **ตั้งชื่ออาร์กิวเมนต์ให้ตรง route parameter** (`Academy $academy, Election $election`) — ดู §7.2 ว่าพลาดข้อนี้แล้วเกิดอะไรขึ้น

## ส่วนที่ 2 — G2: แท็บ "บันทึก" จะว่างเกือบทั้งหมด

`ElectionController::auditLog()` มีปัญหาสองชั้นซ้อนกัน:

**2.1 กรองแค่ 4 action จากทั้งหมด 16**
ตอนนี้ `whereIn('action', [ELECTION_CREATE, ELECTION_UPDATE, ELECTION_DELETE, ELECTION_STATUS_CHANGE])`
→ ขยายให้ครบทุก action ของโดเมนนี้ที่ประกาศไว้ใน `MemberActivityLog` (16 ตัว: create/update/delete/status_change ·
party apply/update/approve/reject/withdraw · voter_roll_lock · station open/close · ballot issue/void · close_count · publish)
**ให้ดึงจากรายการกลางตัวเดียว** อย่าพิมพ์ array ซ้ำสองที่ (เช่นทำ `const ELECTION_ACTIONS` หรือ static method ใน `MemberActivityLog`)

**2.2 กรองด้วย `new_values->election_id` แต่ 9 จุดที่เขียน log ไม่ได้ใส่คีย์นี้ลงไปเลย**
→ ต่อให้ขยายรายการ action แล้ว 9 อย่างนี้ก็ยังกรองไม่เจออยู่ดี:

| ไฟล์ | จุดที่ขาด `election_id` |
|---|---|
| `ElectionPartyService` | `apply` · `update` · `withdraw` · `approve` · `reject` (5 จุด) |
| `ElectionStationService` | `open` · `close` · `void` (3 จุด) — `issue` มีอยู่แล้ว |
| `ElectionVoterRollService` | `lock` (ส่ง `$counts` ล้วน ๆ) |

**วิธีที่ต้องการ:** เติมที่ **เมธอด `log()` ส่วนกลางของแต่ละ service** (มันรับ `Election $e` อยู่แล้ว) ไม่ใช่ไล่เติมทีละ call site
เพื่อไม่ให้คนเพิ่ม action ใหม่ในอนาคตลืมอีก · ข้อมูลเดิมในคีย์ `new_values` ต้องอยู่ครบเหมือนเดิม

**ไม่ต้อง backfill ข้อมูลเก่า** — ตรวจแล้วตาราง `member_activity_logs` มี **0 แถว** ที่ `action LIKE 'election%'`

**ไม่ต้องแก้ `getAvailableActions()`** — ตรวจแล้ว action ของการเลือกตั้งลงทะเบียนครบทั้ง 16 ตัวอยู่แล้วใน
`MemberActivityLogController::getAvailableActions()`

## ส่วนที่ 3 — เทสต์ (ส่วนที่สำคัญที่สุดของงานนี้)

**3.1 เทสต์ของ G1 + G2 ต้องยิงผ่าน route จริง** ด้วย `actingAs($actor, 'api')->getJson()/postJson()`
ห้ามเรียก service หรือเมธอดคอนโทรลเลอร์ตรง ๆ (บทเรียน §7.2 — เทสต์ระดับ service 121 ตัวมองไม่เห็นว่า routing พังทั้งชั้น)

- `GET /{election}/stations` → 200 · เห็นหน่วยที่สร้างไว้ · `issued_count`/`cast_count` ตรงกับความจริงหลังออกบัตร 1 ใบ
- หน่วยของการเลือกตั้งอื่นต้องไม่โผล่ในรายการ
- ผู้ใช้ที่ไม่มี `elections.manage` → 403
- **`GET /{election}/audit-log` หลังเดินครบหนึ่งวงจร** (สร้าง → สมัครพรรค → อนุมัติ → ล็อกบัญชี → เปิดหน่วย → ออกบัตร → ยกเลิกบัตร → ปิดหน่วย → เปลี่ยนสถานะ → ปิดหีบ → ประกาศ)
  ต้อง **เห็นครบทุก action ที่เพิ่งทำ** — นี่คือเคสที่พิสูจน์ว่า G2 ปิดจริง ไม่ใช่แค่ขยาย array
- log ของการเลือกตั้งอื่นต้องไม่ปนเข้ามา

**3.2 ยกเคสที่ claude ตรวจด้วยมือไปแล้วเข้าเป็นเทสต์ถาวร** ใน `tests/Feature/Election/ElectionHttpRoutingTest.php`
(ตอนนี้ไฟล์นั้นมีแค่ 2 เมธอด · เคสข้างล่างนี้ claude ยืนยันแล้วว่าผ่าน แต่ยังไม่มีใครกันถอยหลังไว้):

| เคส | ต้องได้ |
|---|---|
| `POST .../parties/{party}/approve` | 200 และแถวใน DB เป็น `approved` พร้อมเบอร์ที่ขอ |
| หน่วย/พรรคของการเลือกตั้งอื่น | 404 |
| สมาชิกที่มีแค่ `elections.view` เรียก `stations/{s}/open` | 403 |
| `lookup` ด้วย `user_id` ของคนที่ **ไม่มี `member_code`** ขณะที่มีอีกคนถือ `member_code` เท่ากับ `user_id` นั้น | ได้ **คนที่ถูกต้อง** ไม่ใช่ตัวหลอก |
| `lookup` ของคนที่มีรูปทั้งบัตรนักเรียนและ avatar | `photo` ต้องมาจาก **บัตรนักเรียน** |

---

## Acceptance criteria

1. `./vendor/bin/pint` ผ่าน
2. `php artisan test --filter Election` เขียวทั้งชุด — **ฐานปัจจุบัน 123 เทสต์ ห้ามลดลง** + เทสต์ใหม่ตามส่วนที่ 3
3. **แปะผลรันจริง** กลับมา (ไม่ใช่คำบรรยาย) โดยเห็นชื่อเคส audit-log ที่เดินครบวงจรผ่านจริง
4. `php artisan route:list --json` — route ใหม่ของ G1 มี guard `elections.manage` และ route เดิมของโดเมนนี้ยังครบเหมือนเดิม
5. บอกมาให้ชัดว่า **จำนวน action ที่ `audit-log` คืนหลังเดินครบวงจร = กี่ตัว** และเป็นตัวไหนบ้าง

## กฎการทำงาน

- อ่านไฟล์ก่อนแก้เสมอ
- **ห้ามรัน `php artisan migrate:fresh`** — DB มีข้อมูลจริง · งานนี้ไม่ต้องมี migration ใหม่เลย
- ห้ามแตะ `.env` · ห้ามแตะ `ui/` · ห้าม refactor นอกขอบเขต
- **ห้ามแตะพฤติกรรมบัตรลับ** — `cast` ต้องไม่บันทึกอะไรที่ผูกผู้ลงคะแนนกับตัวเลือก (`$actor` ถูกจงใจไม่ใช้ มีคอมเมนต์กำกับ)
  และ log ของ `ballot_issue` บอกได้แค่ว่าใครมารับบัตร ห้ามเพิ่มข้อมูลตัวเลือกลงไปเด็ดขาด
- commit เป็นชุดเล็ก: (1) G1 + เทสต์ · (2) G2 + เทสต์ · (3) เทสต์ที่ยกมาจากส่วน 3.2
- ถ้า commit ไม่ได้ (เคยเจอ `.git/index.lock`) ให้รายงานตรง ๆ แล้วทิ้งงานไว้ใน working tree — อย่าพยายามลบไฟล์ lock เอง
- ถ้าทำข้อไหนไม่ได้ ให้รายงานตรง ๆ **ห้ามรายงานว่าเสร็จทั้งที่เทสต์ยังแดง**
