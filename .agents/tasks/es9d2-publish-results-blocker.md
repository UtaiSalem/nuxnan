# E-S9d2 — ประกาศผลแล้วต้องดูผลได้ (+ ป้ายภาษาไทย + ผลตอนปิดหีบ)

> เขียนโดย claude 2026-08-23 · เมนู #25 · งานต่อจาก E-S9d
> อ่านประกอบ: `.agents/school-admin/25-elections.md` **§11.13** (ผลตรวจที่เป็นที่มาของงานนี้) · **§7.2** (บทเรียนเดียวกันที่เกิดซ้ำ)

## บริบท

E-S9d ทำสามแท็บสุดท้ายขึ้นครบแล้ว (`a1e18db3`) แต่ตอนตรวจด้วยการยิง HTTP จริงพบว่า
**ขั้นสุดท้ายของทั้งระบบเลือกตั้ง — การประกาศผล — ใช้งานไม่ได้**
งานนี้เล็กแต่เป็นบล็อกเกอร์: 1 บั๊ก backend + 1 URL ผิด + 1 เรื่อง UX

---

## 🔴 D1 — `GET /results` ตอบ 404 ตลอด แม้ประกาศผลไปแล้ว

**เป็นบั๊กเก่าตั้งแต่ E-S7 ไม่ใช่ของ E-S9d**

`ElectionResultService::publish()` ทำสามอย่าง:
1. เซ็ต `election_results.published_at` / `published_by`
2. `transitionTo($e, published)`
3. เขียน audit log

**แต่ไม่เคยเซ็ต `elections.published_at`**

ขณะที่ `ElectionController::results()` ใช้

```php
abort_unless($e->published_at, 404);
```

ซึ่งอ่านคอลัมน์ของตาราง **elections** → ด่านนี้ไม่มีวันเปิด

**ยิงจริงเป็นลำดับแล้ว:** `POST /close-and-count` → **200** (คืนผล 1 แถว `votes:1 rank:1 is_winner:true`)
→ `POST /publish` → **200** → `GET /results` → **404**

### สิ่งที่ต้องทำ

- ให้ `publish()` เซ็ต `elections.published_at` (และถ้าเหมาะสมก็ `published_by`) ในทรานแซกชันเดียวกัน
  — คอลัมน์นี้มีไว้เพื่อการนี้อยู่แล้ว และการ์ดกันประกาศซ้ำใน `publish()` เองก็อ่านค่านี้
  (`if ($e->status !== closed || $e->published_at || ! ElectionResult::…exists())`) ซึ่งตอนนี้เงื่อนไขกลางไม่เคยเป็นจริง
- **ห้ามแก้ด้วยการเปลี่ยน `results()` ให้เช็ค `status` แทน** โดยไม่บอก — ถ้าคิดว่าทางนั้นดีกว่าให้เสนอมาก่อน
- ⚠️ ระวังลำดับกับ `transitionTo()` ซึ่งบล็อกการออกจากสถานะ `published` อยู่แล้ว — จัดลำดับให้ประกาศซ้ำยังถูกปฏิเสธเหมือนเดิม

### เทสต์ที่ต้องมี (ระดับ HTTP เท่านั้น)

ในไฟล์เทสต์ที่ยิงผ่าน route จริง (`actingAs($actor,'api')`):

1. เดินครบ: เปิดหน่วย → ออกบัตร → ลงคะแนน → `close-and-count` → **`GET /results` ยัง 404** (ยังไม่ประกาศ)
   → `publish` → **`GET /results` = 200 และเห็นแถวผลจริง** พร้อม `votes` / `rank` / `is_winner`
2. ประกาศซ้ำต้องยังถูกปฏิเสธ
3. เทสต์เดิมทั้งหมดต้องไม่แดง (ฐาน **130**)

> **บทเรียนที่ต้องไม่ทำซ้ำ:** E-S7 มีเทสต์ของ `publish()` อยู่แล้วและเขียวมาตลอด เพราะมันเรียก service ตรง ๆ
> แล้วเช็คแถวใน `election_results` — ไม่เคยยิง endpoint ที่หน้าจอใช้จริง · **เทสต์ของงานนี้ต้องยิงผ่าน HTTP เท่านั้น**

## 🔴 D2 — ป้ายภาษาไทยของแท็บบันทึกเรียก URL ที่ไม่มีอยู่

`useElections.ts` เขียนว่า

```ts
const getActivityActions = (a: number) => api.call(`/api/academies/${a}/activity-log/actions`)
```

แต่ route จริงคือ **`/api/academies/activity-log/actions`** — ไม่มีส่วน `{academy}`

ยิงจริง: แบบมี id → **404** · แบบไม่มี id → **200 คืน 34 action พร้อม label ไทย**
→ ตอนนี้ `labels` ว่างเสมอ แท็บบันทึกโชว์รหัสดิบอย่าง `election_ballot_issue`

**แก้:** ตัดส่วน academy ออกจาก URL · **ยืนยันด้วย `php artisan route:list --json` ก่อน** ว่า URI ตรงจริง
· แล้วยิงจริงดูว่าได้ label ไทยของ action ตระกูล `election_*` ครบ 16 ตัว

## 🟡 D3 — ผลที่นับแล้วตอนสถานะ `closed` ถูกทิ้ง

`POST /close-and-count` **คืนผลกลับมาใน response** (`{ results: [...] }` — ยืนยันจากการยิงจริง)
แต่ `ElectionResultsTab.vue` เรียกแล้ว `window.location.reload()` ทันที ผลจึงหายไป
และ `GET /results` ก็เข้าไม่ได้ในสถานะ `closed` (ตามด่าน 404 ที่ตั้งใจไว้)

→ **แอดมินมองไม่เห็นตัวเลขก่อนกดประกาศ** ซึ่งขัดกับ §11.4 ที่ระบุว่าสถานะ `closed` ต้องโชว์ผลที่แช่ไว้ + ปุ่มประกาศ

**แก้:** เก็บผลจาก response ของ `closeAndCount` ไว้ใน state แล้วแสดงเลย **แทนการ `reload()`**
· ปุ่ม "ประกาศผล" กดต่อได้ทันทีโดยไม่ต้องรีเฟรช · หลัง `publish` ให้โหลดผลจาก `GET /results` ตามปกติ
· เอา `window.location.reload()` ออกจากทั้งสองปุ่ม (ทำให้เสีย state ของทั้งหน้าโดยไม่จำเป็น)

---

## Acceptance criteria

1. `./vendor/bin/pint` ผ่าน · `php artisan test --filter Election` เขียวทั้งชุด (**ฐาน 130 ห้ามลดลง**) + เทสต์ HTTP ของ D1
2. **แปะผลยิง HTTP จริงตามลำดับ**: `close-and-count` → `GET /results` (คาด 404) → `publish` → `GET /results` (คาด **200 พร้อมข้อมูล**)
3. แปะผลยิง endpoint ป้ายภาษาไทยที่แก้แล้ว พร้อมตัวอย่าง label ของ action ตระกูล `election_*`
4. ยืนยันว่าไม่มี `window.location.reload()` เหลืออยู่ใน `ElectionResultsTab.vue` แล้ว
5. รายงานจำนวนบรรทัด/บรรทัดยาวสุดของไฟล์ที่แตะ (ไม่เกิน ~200 ตัวอักษร)
6. รายชื่อไฟล์ที่แตะทั้งหมด

## กฎการทำงาน

- อ่านไฟล์ก่อนแก้เสมอ · **ห้าม `migrate:fresh`** · งานนี้**ไม่ต้องมี migration ใหม่** (คอลัมน์ `elections.published_at` มีอยู่แล้ว)
- ห้ามแตะ `.env` · ห้าม refactor นอกขอบเขต
- **ห้ามแตะไฟล์เหล่านี้**: `ui/components/Modal.vue` · `ui/components/learn/course/groups/ImportFromClassroomsModal.vue` ·
  `ui/components/learn/course/groups/SyncClassroomModal.vue`
- **ห้ามถอยสิ่งที่ผ่านการตรวจไปแล้ว** — สามแท็บที่เพิ่งทำ · การ์ดสรุปที่อ่านจาก `counts` · watcher ที่ track ได้ · การจัดบรรทัด
- ห้ามแตะพฤติกรรมบัตรลับ · ห้ามแสดงคะแนนรายพรรคก่อนปิดหีบ (ผลที่โชว์ตอน `closed` มาจาก `close-and-count` ซึ่งนับเสร็จแล้ว ไม่ใช่การเดาระหว่างเปิดหีบ)
- commit เป็นชุดเล็ก · ถ้า commit ไม่ได้เพราะ `.git/index.lock` ให้รายงานแล้วทิ้งไว้ใน working tree **อย่าลบไฟล์ lock**
- ถ้าทำข้อไหนไม่ได้ ให้รายงานตรง ๆ **ห้ามรายงานว่าเสร็จทั้งที่เทสต์ยังแดง**
