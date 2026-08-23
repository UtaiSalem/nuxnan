# E-S8c — route-model binding ของโดเมนเลือกตั้งพัง + บั๊กระบุตัวตนที่หน่วยเลือกตั้ง

> เขียนโดย claude 2026-08-23 · เมนู #25 การเลือกตั้งสภานักเรียน
> อ่านประกอบ: `.agents/school-admin/25-elections.md` **§7.2** (ต้นเหตุ + ตารางผลยิง HTTP จริง)
> งานนี้ **ต้องจบก่อนใช้งานจริงทุกกรณี** และก่อนเริ่ม E-S9

## บริบท

E-S8b แก้สัญญาข้อมูลจนถูกต้องแล้ว (commit `194bbbb5` + `73d21076`) และเทสต์ 121 ตัวเขียว
แต่พอยิงผ่าน **HTTP จริง** เป็นครั้งแรก พบว่า **ทั้งโดเมนเลือกตั้งฝั่งหน่วย/พรรค เรียกไม่ถึงข้อมูลจริงเลย**

`ImplicitRouteBinding::resolveForRoute()` ของ Laravel จับคู่ route parameter กับอาร์กิวเมนต์ **ด้วยชื่อ**
(`getParameterName()` เทียบชื่อตรงหรือ snake_case) — สองคอนโทรลเลอร์นี้ตั้งชื่อย่อจึงไม่ตรง:

| คอนโทรลเลอร์ | ลายเซ็นปัจจุบัน | route parameter |
|---|---|---|
| `ElectionStationController` — 9 เมธอด (`cast` `store` `update` `destroy` `open` `close` `lookup` `search` `issue` `void` `progress`) | `$a`, `$e`, `$s` | `{academy}` `{election}` `{station}` |
| `ElectionPartyController` — 6 เมธอด (`store` `update` `withdraw` `index` `approve` `reject`) | `$a`, `$e`, `$p` | `{academy}` `{election}` `{party}` |

พอจับคู่ไม่ได้ Laravel **ไม่ throw** — มันข้ามไป แล้ว `RouteDependencyResolverTrait` สร้าง **อินสแตนซ์เปล่า**
จาก container ยัดให้แทน → `abort_if($election->academy_id !== $academy->id, 404)` กลายเป็น `null !== null` = ผ่านด่าน
แล้วไปพัง (404) หรือคืนค่าว่าง (200 แต่ไม่มีข้อมูล) ที่ชั้นล่าง

ผลยิงจริง (feature test ผ่าน `actingAs(..., 'api')` บน route จริง):

| endpoint | ผลตอนนี้ |
|---|---|
| `POST .../stations/{s}/open` · `lookup` · `issue` · `GET .../progress` | **404** `No query results for model [Election]` |
| **`POST .../elections/{e}/cast`** | **422 "ไม่พบบัตรลงคะแนนที่ใช้งานได้" · `election_ballots` 0 แถว** |
| `GET .../stations/{s}/search` · `GET .../elections/{e}/parties` | **200 แต่ข้อมูลว่างเงียบ ๆ** |
| `GET .../elections/{e}` (ตัวควบคุม ชื่ออาร์กิวเมนต์ถูกต้องอยู่แล้ว) | **200 พร้อมข้อมูลจริง** |

`ElectionController` และ `ElectionVoterRollController` **ไม่ต้องแก้** — ตัวแรกตั้งชื่อถูกอยู่แล้ว
ตัวหลังเลี่ยงด้วย `Academy::findOrFail($r->route('academy'))` เอง (ถ้าอยากจัดให้เหมือนกันค่อยทำทีหลัง ไม่ใช่งานนี้)

---

## ขอบเขตงาน

### ส่วนที่ 1 — แก้ชื่ออาร์กิวเมนต์ให้ตรงกับ route parameter

**1.1 `ElectionStationController`** — เปลี่ยนทุกเมธอด: `$a` → `$academy` · `$e` → `$election` · `$s` → `$station`
(พร้อมแก้จุดที่อ้างถึงตัวแปรเหล่านั้นในบอดี้ให้ครบ)

**1.2 `ElectionPartyController`** — เปลี่ยนทุกเมธอด: `$a` → `$academy` · `$e` → `$election` · `$p` → `$party`

**1.3 คืนลายเซ็น `progress()` ให้เหมือนพี่น้อง**
ตอนนี้เป็น `progress(Academy $a, Election $e, string $station)` + `$e->stations()->findOrFail($station)`
ซึ่งเป็นการเลี่ยงบั๊กแบบครึ่งทาง → เปลี่ยนกลับเป็น `progress(Academy $academy, Election $election, ElectionStation $station)`
แล้วใช้ `$this->station(...)` เหมือนเมธอดอื่น **payload ที่คืนต้องเหมือนเดิมทุกคีย์** (`name` `is_open` `location` `issued` `cast` `remaining`)

**1.4 ตรวจว่าไม่มีที่อื่นหลงเหลือ**
ไล่ดูคอนโทรลเลอร์ทุกตัวที่ผูกกับ route ของโดเมนนี้ ถ้ามีเมธอดไหนรับ type-hinted model
โดยชื่ออาร์กิวเมนต์ไม่ตรงกับ route parameter ให้แก้ให้ตรงด้วย

### ส่วนที่ 2 — เทสต์ระดับ HTTP (สำคัญที่สุดของงานนี้)

สร้างไฟล์ใหม่ `tests/Feature/Election/ElectionHttpRoutingTest.php` ที่ยิง **ผ่าน route จริง**
ด้วย `actingAs($actor, 'api')->getJson()/postJson()` ทุกเคส (ห้ามเรียก service หรือเมธอดคอนโทรลเลอร์ตรง ๆ):

| เคส | ต้องได้ |
|---|---|
| `POST .../stations/{station}/open` | **200** และ `is_open` ใน DB เป็น `true` จริง |
| `GET .../stations/{station}/progress` | **200** และ `name` ตรงกับชื่อหน่วยจริง · `is_open` ตรงกับสถานะจริง |
| `POST .../stations/{station}/lookup` | **200** และ `status = eligible` สำหรับผู้มีสิทธิ์จริง |
| `POST .../stations/{station}/issue` | **200** และได้ `ballot_token` |
| `GET .../stations/{station}/search?q=` | **200** และ **เจอแถวจริง** (ไม่ใช่ paginator ว่าง) |
| **`POST .../elections/{election}/cast`** | **200** และ `election_ballots` **เพิ่มขึ้น 1 แถวจริง** |
| `GET .../elections/{election}/parties` | **200** และ **เห็นพรรคที่สร้างไว้** (ไม่ใช่ `[]`) |
| `POST .../parties/{party}/approve` | **200** และสถานะพรรคใน DB เปลี่ยนจริง |
| หน่วย/พรรคของการเลือกตั้งอื่น | **404** (ต้องยังกันข้ามการเลือกตั้งได้เหมือนเดิม) |
| ผู้ใช้ที่ไม่มีสิทธิ์ `elections.station` | **403** (ยืนยันว่าด่านสิทธิ์ยังทำงาน) |

🔴 **เคสที่คาดหวัง 404 อย่างเดียวใช้พิสูจน์อะไรไม่ได้** — บั๊กนี้ทำให้ทุกอย่าง 404 อยู่แล้ว
เทสต์ที่มีคุณค่าคือเคสที่ **คาดหวัง 200 พร้อมข้อมูลจริง**

### ส่วนที่ 3 — บั๊กระบุตัวตนจากช่องค้นหาชื่อ (frontend + backend)

`ui/pages/academies/[name]/elections/[id]/station.vue` → `selectCandidate()` ส่ง
`candidate.member_code || String(candidate.user_id)` เข้า `lookup`
แต่ `StudentIdentifierResolver` **ตีความเลขล้วนเป็น `member_code`**

ตัวเลขจริงของโรงเรียนนี้: สมาชิกอนุมัติที่ไม่มี `member_code` มี **4 คน** · `member_code` อยู่ในช่วง **48–12,848** ·
`user_id` อยู่ในช่วง **2–17,501** · และ **มีอยู่แล้ว 1 คนที่ `user_id` ของตัวเองตรงกับ `member_code` ของคนอื่น**
→ ที่หน่วยเลือกตั้งแปลว่า **กรรมการอาจออกบัตรให้ผิดคน**

ทางแก้ที่ต้องการ: ให้เส้นทาง "เลือกจากผลค้นหา" **ระบุตัวด้วย `user_id` อย่างชัดเจน ไม่ปนกับ `member_code`**
เช่นรับพารามิเตอร์แยกใน `lookup` (`user_id` หรือ `identifier`) แล้วฝั่งหน้าส่ง `user_id` ตรง ๆ เมื่อเลือกจากรายการ
**ห้ามแก้ `StudentIdentifierResolver`** (ใช้ร่วมกับระบบเช็คชื่อ เมนู #18/#26)
ต้องมีเทสต์ยืนยันว่าเลือกคนที่ไม่มี `member_code` จากผลค้นหาแล้วได้ **คนคนนั้นจริง**

### ส่วนที่ 4 — เก็บค้างเล็กน้อยจากรีวิว E-S8b

- เพิ่มเทสต์ยืนยันว่า `lookup` เลือกรูปจาก `student_cards.profile_image` **ก่อน** `users.profile_photo_path`
  และ fallback ถูกต้องเมื่อมีแค่แหล่งเดียว/ไม่มีเลย (เทสต์ปัจจุบันเช็คแค่ `arrayHasKey('photo')`)
- `station.vue`: `seconds.value = data.ballot_ttl_seconds` ควรมี fallback `|| 180` กัน NaN ถ้าคีย์หาย

---

## Acceptance criteria

1. `./vendor/bin/pint` ผ่าน
2. `php artisan test --filter Election` เขียวทั้งชุด — **ฐานปัจจุบัน 121 เทสต์ ห้ามลดลง** + เทสต์ HTTP ใหม่ตามส่วนที่ 2
3. **แปะผลรันจริง** ของข้อ 2 กลับมา (ไม่ใช่คำบรรยาย) โดยต้องเห็นชื่อเคส HTTP ที่คาดหวัง 200 ผ่านจริง
4. ยืนยันว่า `cast` ผ่าน HTTP ทำให้ `election_ballots` **เพิ่มขึ้นจริง** — นี่คือเคสที่พิสูจน์ว่าการเลือกตั้งใช้งานได้
5. `php artisan route:list --json` — ทุก route ของโดเมนนี้ยังมี guard เดิมครบ ไม่มีตัวไหนหลุด

## กฎการทำงาน

- อ่านไฟล์ก่อนแก้เสมอ
- **ห้ามรัน `php artisan migrate:fresh`** — DB มีข้อมูลจริง · งานนี้ไม่ต้องมี migration ใหม่เลย
- ห้ามแตะ `.env` · ห้าม refactor นอกขอบเขต
- ห้ามแก้พฤติกรรมบัตรลับ: `cast` ต้องไม่บันทึกอะไรที่ผูกผู้ลงคะแนนกับตัวเลือก (`$actor` ถูกจงใจไม่ใช้)
- commit เป็นชุดเล็ก: (1) แก้ชื่ออาร์กิวเมนต์ + เทสต์ HTTP · (2) บั๊กระบุตัวตน · (3) ของค้างส่วนที่ 4
- ถ้าทำข้อไหนไม่ได้ ให้รายงานตรง ๆ **ห้ามรายงานว่าเสร็จทั้งที่เทสต์ยังแดง**
