# E-S11a — limiter ที่คีย์ต่อหน่วยเลือกตั้ง + 429 ที่คนไทยอ่านรู้เรื่อง

> เขียนโดย claude 2026-08-24 · เมนู #25 · shard a ของ E-S11
> อ่านประกอบ: `.agents/school-admin/25-elections.md` **§13** (สเปกเต็ม) · **§13.3** (ข้อตัดสินที่ล็อกแล้ว) · **§7.2** (บทเรียนเรื่องเทสต์ที่ไม่ยิงผ่าน route จริง)

## บริบท

`routes/learn/election.php` มี throttle อยู่แล้ว 4 เส้น แต่เป็น `throttle:30,1` / `throttle:60,1`
แบบ **ไม่ตั้งชื่อ limiter** ซึ่ง Laravel จะคีย์ด้วย `user id` (ทุก route อยู่ใต้ `auth:api`)

ปัญหาจริงในวันเลือกตั้ง: โรงเรียนจะเปิดหลายหน่วย และกรรมการประจำหน่วย **มีโอกาสสูงมากที่จะใช้บัญชีเดียวกัน**
→ เพดาน 30 ครั้ง/นาที กลายเป็นเพดานของ *ทั้งการเลือกตั้ง* ไม่ใช่ของหน่วยเดียว
4 หน่วย × 8 คน/นาที = 32 → **เกินเพดาน แล้วนักเรียนที่ยืนต่อแถวอยู่โดนปฏิเสธ**

และเมื่อโดนปฏิเสธ จอของครูจะขึ้นคำว่า **"Too Many Attempts."** เพราะ `handleError` ใน `station.vue`
อ่าน `e?.data?.message` ออกจอตรง ๆ · ไม่บอกด้วยว่าต้องรอกี่วินาที

---

## งานที่ต้องทำ

### A — ตั้ง named limiter 4 ตัวใน `app/Providers/AppServiceProvider.php`

ตอนนี้ไฟล์นี้ **ไม่มี `RateLimiter` เลยสักตัว** (ยืนยันแล้ว) → เพิ่มใน `boot()`

| limiter | ใช้ที่ | คีย์ | เพดาน |
|---|---|---|---|
| `election-issue` | `/issue`, `/void` | `station_id` จาก route | 60/นาที |
| `election-lookup` | `/lookup` | `station_id` จาก route | 120/นาที |
| `election-cast` | `/cast` | `election_id` + user id | 60/นาที |
| `election-candidates` | `/candidates` | `election_id` + user id | 60/นาที |

**⚠️ จุดที่พังง่ายที่สุดของ shard นี้ — `$request->route('station')` คืน "โมเดล" ไม่ใช่ "id"**
route ทุกเส้นใช้ route-model binding อยู่ (`{station}`, `{election}`) → ค่าที่ได้อาจเป็น `ElectionStation`
หรือเป็น string ก็ได้แล้วแต่จังหวะ · **ต้องแปลงให้เป็น id อย่างปลอดภัยเสมอ** เช่นผ่าน helper เล็ก ๆ ตัวเดียว
ที่ใช้ซ้ำได้ทั้ง 4 limiter — ถ้าเผลอเอา object ไปต่อ string จะได้คีย์เพี้ยนแบบเงียบ ๆ และ **เทสต์ข้อ 2 จะจับได้**

**เหตุผลของเพดานต้องเขียนเป็นคอมเมนต์กำกับไว้ในโค้ด** — ผู้มีสิทธิ์ 2,193 คน เปิด ~4 หน่วย 3 ชั่วโมง
= ~3 คน/นาที/หน่วยโดยเฉลี่ย แต่ช่วงพักกลางวันจะกระจุกหนักกว่าค่าเฉลี่ยหลายเท่า → เพดานตั้งไว้กันบอท ไม่ใช่กันแถว

**🔴 ห้ามใช้ IP เป็นคีย์เด็ดขาด** — ทุกหน่วยอยู่หลัง NAT ตัวเดียวกันของโรงเรียน จะกลายเป็นคีย์เดียวทั้งโรงเรียน

### B — เปลี่ยน 4+1 route ใน `routes/learn/election.php` ให้ใช้ limiter ที่ตั้งชื่อ

- `POST /{election}/cast` — `throttle:30,1` → `throttle:election-cast`
- `POST /{election}/stations/{station}/issue` — `throttle:30,1` → `throttle:election-issue`
- `POST /{election}/stations/{station}/lookup` — `throttle:60,1` → `throttle:election-lookup`
- `GET /{election}/candidates` — `throttle:60,1` → `throttle:election-candidates`
- `POST /{election}/stations/{station}/void` — **ยังไม่มี throttle เลย** → เพิ่ม `throttle:election-issue`

**ห้ามแตะ middleware `academy.permission:*` ของเส้นไหนทั้งนั้น** — สิทธิ์ทั้งหมดถูกตรวจและยืนยันไปแล้วใน E-S10d2
โดยเฉพาะ `/cast` ที่**จงใจไม่มี permission guard** (ใช้ `ballot_token` เป็นหลักฐานแทน ตาม §5) → อย่าไป "ช่วยเพิ่ม" ให้

### C — 429 ตอบเป็น JSON ไทยพร้อม `retry_after`

เพิ่ม render ของ `ThrottleRequestsException` ใน `bootstrap/app.php` (`withExceptions`) ข้าง ๆ ตัวที่มีอยู่แล้ว
สำหรับ `AuthenticationException` · ยึด **สัญญาที่ล็อกไว้แล้วใน §13.5** เป๊ะ ๆ เพราะ shard c เขียนจอตามอันนี้:

```json
{ "success": false, "message": "ระบบกำลังรับคำขอถี่เกินไป กรุณารอสักครู่แล้วลองใหม่", "retry_after": 12 }
```

- `retry_after` เอามาจาก header `Retry-After` ที่ Laravel ใส่มาให้อยู่แล้ว (หน่วยเป็นวินาที)
- **ต้องคง header `Retry-After` ไว้ในการตอบด้วย** ห้ามกลืนหายไปตอน render ใหม่
- ให้มีผลเฉพาะเมื่อ `$request->is('api/*')` หรือ `expectsJson()` เหมือนตัว `AuthenticationException`

**⚠️ ข้อนี้กระทบข้ามโดเมน — ต้องตรวจก่อนแก้**
`routes/earn/donate.php`, `routes/public/courses.php`, `routes/studentcard/studentcard.php` ก็ใช้ `throttle:` อยู่
→ ทั้งหมดจะเปลี่ยนจาก `{"message":"Too Many Attempts."}` มาเป็นรูปแบบใหม่ตามไปด้วย
**ก่อนแก้ให้ grep ทั้ง `ui/` หาข้อความ `Too Many Attempts` หรือการเช็ค status 429 ที่มีอยู่** —
ถ้าเจอที่ไหนที่ผูกกับข้อความเดิม **ให้หยุดแล้วรายงาน อย่าแก้เอง** · ถ้าไม่เจอ ให้ทำต่อได้และบอกในรายงานว่าตรวจแล้วไม่เจอ

---

## เทสต์ที่ต้องมี (ระดับ HTTP เท่านั้น)

เขียนในไฟล์ใหม่ `tests/Feature/Election/ElectionRateLimitTest.php` ยิงผ่าน route จริงด้วย `actingAs($actor,'api')`

1. **ยิง `/issue` จนเต็มเพดานที่หน่วย A แล้วได้ 429** — และ body ตรงตามสัญญาข้อ C ทั้ง 3 คีย์
   พร้อมมี header `Retry-After`
2. **🔴 เทสต์ที่เป็นหัวใจของ shard นี้ — คีย์ต้องแยกตามหน่วยจริง**
   ยิงจนเต็มเพดานที่ **หน่วย A** แล้ว **หน่วย B ต้องยังยิงได้ตามปกติด้วยบัญชีเดียวกัน**
   → ถ้าเทสต์นี้ไม่มี ถือว่า shard นี้ยังไม่ได้แก้อะไรเลย
3. `/cast` เต็มเพดานแล้วยังต้องไม่กระทบ `/issue` (คนละ limiter)
4. เทสต์เดิมทั้งหมดต้องไม่แดง — **ฐาน 141 เทสต์ / 312 assertions ห้ามลดลง** (claude รันยืนยันเองแล้ว 2026-08-24)

> **บทเรียน §7.2 ที่ต้องไม่ทำซ้ำ:** เทสต์ที่เรียก service/controller ตรง ๆ **พิสูจน์เรื่อง rate limit ไม่ได้เลย**
> เพราะ limiter อยู่ที่ชั้น middleware · ทุกเคสของงานนี้ต้องเป็น HTTP เท่านั้น
> และอย่าลืมว่า `RefreshDatabase` ไม่ล้าง rate limiter cache ให้ → **ต้องเคลียร์ limiter เองใน `setUp()`**
> ไม่งั้นเทสต์จะติดกันเองแบบสุ่มและหาสาเหตุยากมาก

---

## เกณฑ์ที่ claude จะใช้ตรวจ (ตรวจเอง ไม่เชื่อรายงาน)

1. `git diff --stat` + อ่าน diff ทุกไฟล์ ดูเลข deletion
2. `php artisan route:list --path=elections` — ทั้ง 5 เส้นต้องโชว์ limiter ที่ตั้งชื่อ **ไม่ใช่ `throttle:30,1`**
3. ยิง 429 ให้เกิดจริงด้วย HTTP แล้วดู body + header ด้วยตาตัวเอง
4. `./vendor/bin/pint --test` ผ่าน
5. `php artisan test --filter Election` — **≥ 141 เทสต์ / 312 assertions**

## ขอบเขต — ห้ามเกินนี้

- **ห้ามแตะ `ui/`** ทั้งหมด (จอเป็นงานของ shard c)
- **ห้ามแตะ** `ElectionStationService` · `ElectionBallotService` · `ElectionResultService` · `ElectionStationController`
  (ทั้งหมดเป็นของ shard b ที่ทำขนานกันอยู่ — **แก้ทับกันแล้วจะชนกัน**)
- **ห้ามแก้ `issue()`** — §13.3 ข้อ 2 อธิบายไว้แล้วว่าทำไมไม่ต้องแก้
- **ห้ามรัน migration หรือแตะ schema** — งานนี้ไม่มีส่วนไหนแตะฐานข้อมูล
- ถ้าเจออย่างอื่นที่คิดว่าเป็นบั๊ก **ให้รายงาน อย่าแก้เอง**
