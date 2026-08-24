# E-S12a2 — สัญญา API ที่หลุดจากสเปก + เทสต์ที่ขาดทั้ง 8 เคส

> เขียนโดย claude 2026-08-24 · เมนู #25 · งานเก็บตกจากผลตรวจ E-S12a
> อ่านประกอบ: `.agents/tasks/es12a-council-from-results-backend.md` · `.agents/school-admin/25-elections.md` **§14.4** (สัญญาที่ล็อกไว้)

## บริบท

E-S12a เขียน service + การ์ด 3 ชั้นได้ถูกต้อง และ **แก้ T2 ได้ดี** — ดึง seed permission ไปไว้ที่
`AcademyGroupPermissionService::seedDefaults()` จุดเดียวแล้วให้ `AcademyGroupController::store()` เรียกใช้
(ไม่ copy-paste ตามที่สั่ง)

**แต่รูปร่าง response ไม่ตรงกับสัญญา §14.4 ที่ล็อกไว้ และ shard b เขียนหน้าจอตามสัญญานั้นไปแล้ว**
ผลคือฟีเจอร์นี้ **กดแล้วพังทั้งสองเส้นทาง** และ **ไม่มีเทสต์เลยสักตัว** (ชุดเทสต์ยังเป็น 147 เท่าเดิม)

---

## 🔴 A — response ตอนสำเร็จ ทำให้ลิงก์ชี้ไป `undefined`

ตอนนี้ controller ตอบ

```php
return response()->json(['success' => true, 'group' => $councils->create(...)], 201);
```

หน้าจอ (`ElectionResultsTab.vue`) อ่าน `response?.data || response` แล้วหยิบ `.id` / `.name`
→ ไม่มีคีย์ `data` → ตกไปใช้ทั้ง body → `body.id` เป็น `undefined`
→ **ลิงก์กลายเป็น `/academies/{name}/groups/undefined`**

**และคีย์ `group` ยังขัดกับธรรมเนียมของโดเมนนี้เองด้วย** — ทุก endpoint ใน `ElectionController`
ตอบ `['success' => true, 'data' => ...]` (เช่น `turnout`, `results`)

→ **แก้เป็น `['success' => true, 'data' => $group]`** · **ห้ามแก้ฝั่ง `ui/`** หน้าจอเขียนถูกตามสัญญาแล้ว

## 🔴 B — response ตอนถูกปฏิเสธ ไม่มี `group_id` / `group_name`

ตอนนี้ service ยัดข้อมูลลงในข้อความ

```php
throw ValidationException::withMessages(['election' => "ตั้งสภานักเรียนแล้ว (group_id: {$existing->id}, group_name: {$existing->name})"]);
```

ผลเสียสองชั้น:
1. หน้าจออ่าน `errData.group_id` ไม่เจอ → **ปุ่ม "ดูสภานักเรียน" ไม่ขึ้นเลย** ซึ่งเป็นทั้งหมดของข้อตัดสิน G4
2. ครูเห็นข้อความไทยที่มีคำว่า `group_id:` กับ `group_name:` โผล่กลางประโยค

→ ตาม §14.4 เคสนี้ต้องตอบ

```json
{ "success": false, "message": "ตั้งสภานักเรียนจากการเลือกตั้งนี้ไปแล้ว", "group_id": 12, "group_name": "…" }
```

`message` เป็นภาษาไทยล้วน ๆ ไม่มีชื่อฟิลด์ปนใน — id/ชื่อ อยู่ในฟิลด์ของตัวเอง
**อีกสองเคส (ยังไม่ประกาศผล · เสมอกัน) รูปแบบเดิมใช้ได้แล้ว** แค่ต้องมี `message` ภาษาไทยเหมือนเดิม
· ข้อความเสมอกันต้องยังบอกชื่อพรรคและคะแนนครบ (G3)

## 🔴 C — ไม่มีเทสต์เลยสักตัว

ชุดเทสต์ยังเป็น **147 เทสต์ / 449 assertions** เท่าเดิมเป๊ะ · สเปกเดิมสั่งไว้ 8 เคส
→ เขียนให้ครบตามนี้ **ทุกเคสยิงผ่าน route จริงด้วย `actingAs($actor,'api')`** (บทเรียน §7.2)

1. สำเร็จ → กลุ่มถูกสร้าง `type = 'student_council'` และ `settings.election_id` ตรง
   **และ response ต้องมี `data.id` (ยืนยันข้อ A ว่าแก้จริง)**
2. สมาชิกครบ · `leader` ยังมี `role = 'leader'` ไม่ถูกยุบเป็น member · `status = 2` ทุกแถว
3. `leader` อยู่ใน `academy_group_admins`
4. **permission ครบ** — จำนวนแถวเท่ากับจำนวน key ใน `AcademyGroupPermissions::PERMISSIONS`
5. การ์ด 1 — ยังไม่ประกาศผล → 422
6. การ์ด 2 — สองพรรคคะแนนเท่ากัน → 422 **และ `message` ต้องมีชื่อพรรคที่เสมอทั้งสอง**
7. การ์ด 3 — เรียกซ้ำ → 422 · **response ต้องมี `group_id` ตรงกับกลุ่มเดิม (ยืนยันข้อ B)**
   · **และต้องยืนยันว่าในฐานมีกลุ่ม `student_council` แค่กลุ่มเดียว**
8. สิทธิ์ — คนที่มีแค่ `elections.view` → 403

---

## 🟡 D — `role` ของ `academy_group_admins` ไม่ตรงกับที่ระบบใช้

service เขียน `'role' => 'admin'` แต่ `AcademyGroupAdminController::store()` ของระบบใช้
`$validated['role'] ?? 'leader'` และคอลัมน์นี้ default เป็น `'leader'`
→ **เปลี่ยนเป็น `'leader'`** ให้ตรงกับที่เหลือของระบบ (ตารางนี้ยังไม่มีข้อมูลจริงเลย ตอนนี้เปลี่ยนได้ฟรี)

## 🟡 E — การ์ดอยู่นอก transaction

การ์ดทั้ง 3 ชั้นรันก่อน `DB::transaction()` → เป็น check-then-act
สองคำขอพร้อมกันผ่านการ์ด 3 ได้ทั้งคู่ แล้วสร้างสภานักเรียนสองกลุ่ม
→ **ย้ายการ์ดเข้าไปอยู่ใน transaction เดียวกับการสร้าง** (ความเสี่ยงจริงต่ำเพราะเป็นปุ่มของแอดมิน
แต่ต้นทุนการแก้ต่ำกว่ามาก และเคสที่ 7 จะกลายเป็นเทสต์ที่มีความหมายจริง)

## 🟡 F — `$leader` เป็น null ได้

`$winner->party->members->firstWhere('role', 'leader')` แล้วใช้ `$leader->user_id` ทันที
→ ถ้าพรรคที่ชนะไม่มีสมาชิกบทบาท leader (ข้อมูลเพี้ยน) จะ fatal error
→ **ถ้าไม่เจอ leader ให้โยนข้อความไทยที่อ่านรู้เรื่อง** ไม่ใช่ปล่อยให้ null pointer

---

## เกณฑ์ที่ claude จะใช้ตรวจ (ตรวจเอง ไม่เชื่อรายงาน)

1. `git diff --stat` + อ่าน diff ทุกไฟล์ ดูเลข deletion
2. **ยิง endpoint จริงแล้วดู body ด้วยตาเอง** ทั้งเส้นทางสำเร็จและเคส "ตั้งซ้ำ"
   ว่ามี `data.id` และ `group_id` จริงตามสัญญา
3. **เทียบคีย์ที่ `ElectionResultsTab.vue` อ่าน กับ body จริง** — บทเรียน §7.1 · **ห้ามแก้ฝั่ง `ui/` เพื่อให้เข้ากับ backend**
4. `./vendor/bin/pint --test` ผ่าน
5. `php artisan test --filter Election` — **ต้องได้ ≥ 155 เทสต์** (ฐาน 147 + 8 เคส) และ assertions ไม่ต่ำกว่า 449

## ขอบเขต — ห้ามเกินนี้

- **ห้ามแตะ `ui/`** ทั้งหมด — หน้าจอเขียนตามสัญญา §14.4 ถูกแล้ว ฝั่งที่หลุดคือ backend
- **ห้ามเปลี่ยนข้อตัดสิน §14.1** (G1–G4) — แก้แค่รูปร่าง response ไม่ใช่พฤติกรรม
- **ห้ามแตะ `seedDefaults()` และ `AcademyGroupController::store()`** — ส่วนนั้นทำถูกแล้ว
- **ห้ามเขียน migration ห้ามแตะ schema ห้ามรัน `php artisan migrate` ทุกรูปแบบ**
- **ห้ามแก้ `closeAndCount()`**
- ถ้าเจออย่างอื่นที่คิดว่าเป็นบั๊ก **ให้รายงาน อย่าแก้เอง**
