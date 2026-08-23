# E-S11b — กวาดใบเสร็จค้าง + ตัวเลขบนจอหน่วยที่ไม่ขึ้นกับ cron

> เขียนโดย claude 2026-08-24 · เมนู #25 · shard b ของ E-S11
> อ่านประกอบ: `.agents/school-admin/25-elections.md` **§13** (สเปกเต็ม) · **§13.3 ข้อ 1 และ 2** (ข้อตัดสินที่ล็อกแล้ว — อ่านก่อนเขียนโค้ด) · **§13.4**

## บริบท

`ElectionStationService::expireStale(Election $e)` **มีอยู่แล้ว** (บรรทัด ~135) และทำงานถูกต้อง
แต่ถูกเรียกที่เดียวคือใน `ElectionResultService::closeAndCount()` → **ระหว่างวันเลือกตั้งไม่มีอะไรกวาดเลย**

ผลที่ตามมาคือตัวเลขบนจอหน่วย: `progress()` นับ `status='issued'` ดิบ ๆ →
ใบเสร็จของคนที่รับบัตรแล้วเดินหายไป **ยังถูกนับเป็นบัตรค้างตลอดวัน** ตัวเลขมีแต่ขึ้นไม่มีลง

---

## 🔴 อ่านก่อน — สองข้อที่ตัดสินไปแล้ว ห้ามออกแบบใหม่

**1. ความถูกต้องของตัวเลขต้องไม่ขึ้นกับ cron — cron เป็นแค่การทำความสะอาด**
`routes/console.php` มี 12 บรรทัดที่ขึ้นกับ cron `* * * * * php artisan schedule:run` ซึ่ง runbook ของโปรเจคเอง
ยังสั่งว่า "ต้องยืนยัน" และ **บนเครื่อง dev (WAMP/Windows) แทบแน่ว่าไม่มี**
→ ดังนั้น `progress()` ต้องนับให้ถูกด้วยตัวเองด้วยเงื่อนไขเวลาสด **ไม่ใช่รอ scheduler มาเปลี่ยน status ให้ก่อน**

**2. งานนี้ไม่ใช่การอุดรูรั่วความปลอดภัย — อย่าเขียนโค้ดเกินจริง**
ตรวจแล้วว่า `issue()` เขียนทับใบเสร็จเดิมได้ทุกสถานะยกเว้น `cast` → ใบเสร็จค้างที่ยังไม่ถูกกวาด
**ไม่ได้บล็อกการออกบัตรใหม่** · และ `cast()` เช็ค `token_expires_at->isFuture()` อยู่แล้ว
→ **ห้ามแก้ `issue()` และห้ามแก้ `cast()`** งานนี้เป็นเรื่องความสะอาดกับความถูกต้องของตัวเลขเท่านั้น

---

## งานที่ต้องทำ

### A — `expireStaleAll()` ใน `ElectionStationService`

เพิ่มเมธอดที่กวาดข้ามทุกการเลือกตั้งในครั้งเดียว **โดยใช้เงื่อนไขชุดเดียวกับ `expireStale()` ที่มีอยู่**
(`status = 'issued'` และ `token_expires_at < now()` → เซ็ต `status = 'expired'`, `token_hash = null`)
คืนค่าเป็นจำนวนแถวที่ถูกกวาด

**🔴 ห้าม copy-paste เงื่อนไขไปไว้สองที่** — ถ้าวันหนึ่งนิยาม "ค้าง" เปลี่ยน แล้วสองที่ไม่ตรงกัน
จะกลายเป็นบั๊กที่หาไม่เจอ → ให้ `expireStale()` กับ `expireStaleAll()` ใช้ predicate ตัวเดียวกันจริง ๆ
(scope บนโมเดล หรือ private method ก็ได้ เลือกทางที่อ่านง่ายที่สุด)
· `expireStale(Election $e)` เดิม **ต้องคงพฤติกรรมเดิมทุกอย่าง** เพราะ `closeAndCount()` พึ่งมันอยู่ก่อนตรวจ invariant §2.3

### B — command `elections:expire-stale-receipts`

`app/Console/Commands/` — **ใช้ `CleanupCoursePointReservations.php` เป็นต้นแบบ** (โครงเดียวกัน สั้น ๆ `handle()` เดียวจบ)

- เรียก `expireStaleAll()` แล้ว `$this->info(...)` บอกจำนวนแถวที่กวาด
- คืน `self::SUCCESS`
- **ต้องรันด้วยมือได้และอ่านผลรู้เรื่อง** เพราะวันจริงอาจไม่มี cron (ดูข้อ 1 ข้างบน)

เพิ่มใน `routes/console.php` ต่อจากบรรทัดที่มีอยู่:
`Schedule::command('elections:expire-stale-receipts')->everyFiveMinutes();`
(ความถี่เดียวกับ `course-points:cleanup-reservations` ที่เป็น TTL cleanup แบบเดียวกัน)

### C — `progress()` นับด้วยเงื่อนไขเวลาสด

`ElectionStationController::progress()` (~บรรทัด 133) ตอนนี้เป็น

```php
'issued' => $s->receipts()->where('status', 'issued')->count(),
```

→ ต้องกลายเป็น "บัตรที่ยังค้างอยู่จริง" = `status = 'issued'` **และ** `token_expires_at > now()`

- คีย์ `issued` / `cast` / `remaining` / `name` / `is_open` / `location` **ห้ามเปลี่ยนชื่อ**
  `station.vue` อ่านอยู่ และสัญญาข้อมูลชุดนี้เพิ่งถูกจัดให้ตรงกันใน E-S8b — อย่าไปรื้อ
- `cast` กับ `remaining` **ไม่ต้องแก้** ทั้งคู่นับจาก `status='cast'` ซึ่งไม่มีวันหมดอายุ

### D — `turnout.issued` → `receipts_total`

`ElectionResultService::turnout()` (~บรรทัด 80) มีคีย์ `issued` ที่นับใบเสร็จ **ทุกสถานะรวมกัน**
ซึ่งชนกับความหมายของ `progress.issued` (บัตรค้างที่หน่วยนั้น) — ชื่อเดียวกัน คนละเรื่อง

→ เปลี่ยนชื่อคีย์เป็น `receipts_total`
**ตรวจแล้วว่าปลอดภัย** ไม่มีหน้าไหนใน `ui/` อ่าน `turnout.issued` เลย
(`results.vue` อ่านแค่ `voted` / `total` / `percentage` / `by_grade_level` / `by_station` ·
`ElectionOverviewTab.vue` อ่านแค่ `percentage`)
→ **แต่ให้ grep ยืนยันเองอีกรอบก่อนแก้** ถ้าเจอที่ไหนที่อ่านอยู่ **ให้หยุดแล้วรายงาน**
· คีย์ `voted` / `total` / `percentage` / `by_*` **ห้ามแตะ** — `percentage` เพิ่งถูกแก้ความหมายไปใน E-S10a (A4)

---

## เทสต์ที่ต้องมี

เพิ่มในไฟล์เทสต์ของโดเมนนี้ (`ElectionStationTest` มีเคส `expireStale` อยู่แล้ว 2 เคส ใช้เป็นแบบได้)

1. **`expireStaleAll()` กวาดข้ามการเลือกตั้ง** — สร้างใบเสร็จหมดอายุใน 2 election แล้วรันครั้งเดียว
   ต้องกวาดครบทั้งสองฝั่ง และ `token_hash` เป็น NULL หมด
2. **ใบเสร็จที่ยังไม่หมดอายุต้องไม่ถูกแตะ** และใบเสร็จ `cast` ต้องไม่ถูกแตะเด็ดขาด
3. **🔴 เคสที่พิสูจน์ข้อตัดสิน §13.3 ข้อ 1** — ใบเสร็จหมดอายุแล้วแต่ **ยังไม่รัน command เลย**
   → `GET /progress` ต้องคืน `issued = 0` ทันที (ยิงผ่าน route จริง `actingAs(...,'api')`)
   ถ้าเทสต์นี้ไม่มี ถือว่ายังไม่ได้ทำตามข้อตัดสิน
4. **`closeAndCount()` ต้องยังทำงานเหมือนเดิมเป๊ะ** — invariant §2.3 ยังตรวจผ่าน (เทสต์เดิมคุมอยู่แล้ว ห้ามให้แดง)
5. เทสต์เดิมทั้งหมดต้องไม่แดง — **ฐาน 141 เทสต์ / 312 assertions ห้ามลดลง** (claude รันยืนยันเองแล้ว 2026-08-24)

---

## เกณฑ์ที่ claude จะใช้ตรวจ (ตรวจเอง ไม่เชื่อรายงาน)

1. `git diff --stat` + อ่าน diff ทุกไฟล์ ดูเลข deletion
2. **รัน command ด้วยมือกับ DB จริง** ใน `DB::beginTransaction()` … `rollBack()` — สร้างใบเสร็จหมดอายุขึ้นมาก่อน
   แล้วดูว่าจำนวนแถวที่กวาดตรงกับที่สร้าง และ `token_hash` เป็น NULL หมด
3. `php artisan schedule:list` เห็นบรรทัดใหม่
4. `./vendor/bin/pint --test` ผ่าน
5. `php artisan test --filter Election` — **≥ 141 เทสต์ / 312 assertions**

## ขอบเขต — ห้ามเกินนี้

- **ห้ามแตะ `ui/`** ทั้งหมด
- **ห้ามแตะ** `routes/learn/election.php` · `AppServiceProvider.php` · `bootstrap/app.php`
  (ทั้งหมดเป็นของ shard a ที่ทำขนานกันอยู่ — **แก้ทับกันแล้วจะชนกัน**)
- **ห้ามแก้ `issue()` / `cast()`** — เหตุผลอยู่ในข้อ 2 ด้านบน
- **ห้ามรัน migration หรือแตะ schema** — งานนี้ไม่ต้องเพิ่มคอลัมน์หรือ index ใด ๆ
  (index `['election_id','status']` ที่มีอยู่รองรับ query ของการกวาดแล้ว)
- **ห้ามรัน `migrate:fresh`** เด็ดขาด — DB เครื่องนี้มีข้อมูลจริง (นักเรียน 2,931 คน)
- ถ้าเจออย่างอื่นที่คิดว่าเป็นบั๊ก **ให้รายงาน อย่าแก้เอง**
