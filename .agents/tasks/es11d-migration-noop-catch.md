# E-S11d — ลบ try/catch ที่ไม่ทำอะไรออกจาก migration ของ E-S10d2

> เขียนโดย claude 2026-08-24 · เมนู #25 · shard d ของ E-S11 · **งานเล็กมาก ไฟล์เดียว**
> อ่านประกอบ: `.agents/school-admin/25-elections.md` **§10 Review Log รายการ 2026-08-24 E-S10d + E-S10d2**

## บริบท

`database/migrations/2026_08_24_000001_backfill_election_permissions_and_member_roles.php`
เหลือเศษจากตอนแก้บั๊กรอบ E-S10d — `up()` มี

```php
try { ... } catch (Throwable $e) { throw $e; }
```

ซึ่ง **จับแล้วโยนต่อเฉย ๆ ไม่ทำอะไรเลย** ไม่กระทบการทำงาน แต่เป็นกับดักสำหรับคนอ่านทีหลัง
เพราะมันดูเหมือนมีการจัดการ error อยู่ทั้งที่ไม่มี

## 🔴 ทำไมต้องระวังเป็นพิเศษกับ migration ตัวนี้

รอบ E-S10d migration ตัวนี้ **เคยทำฐาน dev เพี้ยนมาแล้ว** — `Schema::create()` ทำให้ MySQL implicit commit
แล้ว `catch` ตอนนั้นไป `dropIfExists` ตารางบันทึกทิ้ง สมาชิก 2,447 คนถูกผูก role ไปแล้วโดยไม่มีประวัติให้ย้อน
· ตอนนี้ migration **รันไปแล้วบนฐาน dev** และ `down()` พิสูจน์แล้วว่าคืนค่าได้ตรงเป๊ะ

→ **ห้ามเปลี่ยนตรรกะใด ๆ ทั้งสิ้น** เอาออกแค่ `try`/`catch` ที่ไม่ทำอะไร ให้เนื้อในเหมือนเดิมทุกบรรทัด
· ถ้าเอา `Throwable` ออกจนกลายเป็น import ที่ไม่ถูกใช้แล้ว ให้ลบ `use` นั้นด้วย

## สิ่งที่ต้องทำ

1. ลบ `try { ... } catch (Throwable $e) { throw $e; }` เหลือแค่เนื้อในตามเดิม
2. ลบ import ที่ไม่ถูกใช้แล้ว (ถ้ามี)
3. **ห้ามแตะ `down()`** และห้ามแตะเงื่อนไขที่โยน `RuntimeException` เฉพาะตอนมีสมาชิกที่ต้องผูก role จริงแต่หา role ไม่เจอ
   — เงื่อนไขนั้นคือสิ่งที่ทำให้ฐานเปล่า (SQLite ตอนเทสต์) จบแบบ no-op แทนที่จะพังทั้งชุด

## เกณฑ์ที่ claude จะใช้ตรวจ

1. `git diff` — ต้องเห็นแค่การถอดโครง try/catch ออก **ไม่มีบรรทัดตรรกะเปลี่ยนเลย**
2. `./vendor/bin/pint --test` ผ่าน
3. `php artisan test --filter Election` — **ฐาน 147 เทสต์ / 449 assertions ห้ามลดลง**
   (มีเทสต์ round-trip ของ migration ตัวนี้อยู่ใน `ElectionPermissionBackfillMigrationTest` — ต้องยังเขียว)
4. `php artisan migrate:status` — migration ตัวนี้ต้องยังเป็น `Ran` เหมือนเดิม

## ขอบเขต — ห้ามเกินนี้

- **ห้ามรัน `php artisan migrate`, `migrate:rollback`, `migrate:refresh` หรือ `migrate:fresh`** เด็ดขาด
  — migration ตัวนี้รันไปแล้ว การแก้ครั้งนี้ไม่ต้องรันซ้ำ และ DB เครื่องนี้มีนักเรียนจริง 2,931 คน
- **ห้ามแตะไฟล์อื่น** นอกจาก migration ไฟล์เดียวนี้
- **ห้ามแตะ `ui/`**
- ถ้าเจออย่างอื่นที่คิดว่าเป็นบั๊ก **ให้รายงาน อย่าแก้เอง**
