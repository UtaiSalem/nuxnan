# 07 — ตั้งค่าโรงเรียน

> ไฟล์รองของเมนู **#7 ตั้งค่าโรงเรียน** ใน [OVERVIEW.md](OVERVIEW.md)
> สถานะ: 🔴 **audit เสร็จ 2026-08-28 — พบ gap 12 ข้อ (P0 ด้านความปลอดภัย 2 ข้อ)** ยังไม่ implement

---

## 1. Scope & Purpose

เมนูนี้คือ **ที่เดียวที่ผู้ดูแลใช้แก้ "ตัวโรงเรียน" เอง** ไม่ใช่ข้อมูลที่อยู่ในโรงเรียน
(สมาชิก/นักเรียน/คอร์ส/การเงิน อยู่เมนูอื่น)

ครอบคลุม:
- **อัตลักษณ์โรงเรียน** — ชื่อไทย/อังกฤษ คำอธิบาย โลโก้ รูปปก คำขวัญ ปีที่ก่อตั้ง ประเภท
- **ข้อมูลติดต่อ** — อีเมล โทรศัพท์ เว็บไซต์ ที่อยู่ จังหวัด ประเทศ
- **นโยบายการมองเห็น** — สาธารณะ/ส่วนตัว แสดงรายชื่อสมาชิก/คอร์สหรือไม่
- **นโยบายการเข้าร่วม** — โหมดรับสมัคร (เปิด/อนุมัติ/เชิญเท่านั้น) ใครสมัครได้บ้าง
- **สวิตช์ระดับโรงเรียนที่ระบบอื่นอ่านไปใช้** — เช่น เปิด/ปิดระบบคำร้องทำบัตรนักเรียน,
  ฟิลด์ที่นักเรียนแก้เองได้, เปิด/ปิดการรับบริจาค
- **โซนอันตราย** — ลบ/ปิดโรงเรียน

**ขอบเขตที่ไม่ใช่เมนูนี้:** `admin/school-management.vue` (เมนู #8) เป็นแค่ **หน้ารวมลิงก์**
ไม่มีการตั้งค่าจริงอยู่ในนั้นเลย · ปีการศึกษา/ระดับคะแนน อยู่ใต้ gradebook (เมนู #19)

**ผู้ใช้:** owner, director, admin (แก้ได้) · finance_staff (มี `settings.view` แต่ปัจจุบันเข้าไม่ได้ ดู G6)

---

## 2. Current State (จากการสแกนโค้ดจริง 2026-08-28)

### Frontend
- **Page:** `ui/pages/academies/[name]/admin/settings.vue` (556 บรรทัด) — หน้าเดียวจบ ไม่มี component/composable/store แยก
  - 5 แท็บ: ข้อมูลทั่วไป · ข้อมูลติดต่อ · ความเป็นส่วนตัว · การลงทะเบียน · โซนอันตราย
  - โหลดด้วย `GET /api/academies/{name}` → `populateForm()` → กด "บันทึก" ส่ง **ทุกแท็บพร้อมกัน**
    เป็น `FormData` ไป `POST /api/academies/{id}/settings`
  - ด่านสิทธิ์ฝั่งหน้า: `!can('settings.manage') && !isOwner` → `navigateTo(.../admin)`
- **ลิงก์เข้าเมนู:** `admin.vue:278` (sidebar), `admin/index.vue:246`, `dashboard/admin.vue:166`
- **ไม่มีหน้าอื่นใดในแอปที่แก้ค่าโรงเรียนได้** — ยืนยันด้วย grep: `PATCH /academies/{id}/update` ไม่มีผู้เรียกใน `ui/` เลย

### Backend
- **Controller:** `app/Http/Controllers/Api/Learn/Academy/AcademyController.php`
  - `updateSettings()` บรรทัด 478–586 — ตัวจริงของเมนูนี้
  - `update()` บรรทัด 189–236 — ของเก่าสไตล์ web (`redirect()->back()`) **ไม่มีการตรวจสิทธิ์ใด ๆ** (ดู G1)
  - `destroy()` — **เมธอดว่างเปล่า** และไม่มี route ชี้มา (ดู G2)
- **Routes:** `routes/learn/academy.php`
  - `240: POST /{academy}/settings` → `updateSettings` — **ไม่มี middleware `academy.permission`** ตรวจสิทธิ์เองใน controller
  - `141: PATCH /{academy}/update` → `update` — อยู่ในกลุ่ม `auth:api` เฉย ๆ
  - `104: GET /{academy:name}` → `show` — bind ด้วยคอลัมน์ `name` (ไม่ใช่ `name_slug`)
- **Models:** `Academy.php` (`$fillable` whitelist, `getSettings()` cache 24 ชม.), `AcademySetting.php` (`$guarded = []`, ล้าง cache ตอน saved/deleted)
- **Resource:** `app/Http/Resources/Learn/Academy/AcademyResource.php` — คืนค่า setting ครบทุกตัวที่หน้าใช้ ✅
- **Middleware ที่ควรใช้แต่ไม่ได้ใช้:** `CheckAcademyPermission` (`academy.permission:<key>`) — ตัวนี้ครอบ superadmin +
  `Academy::isAdmin()` + สถานะสมาชิก `status = 2` + สิทธิ์ที่ได้จากฝ่าย/กลุ่ม
- **Tests:** ❌ **ไม่มีเลยสักไฟล์** (grep `updateSettings`/`academy_settings` ใน `tests/` = 0)
- **Audit log:** ❌ ไม่มี — `AuditLogService` มีอยู่และเมนูอื่นใช้ แต่การแก้ตั้งค่าโรงเรียนไม่ถูกบันทึก

### Database

**`academies`** (34 คอลัมน์) — ที่เมนูนี้แก้ได้จริง 10: `name`, `name_en`, `description`, `description_en`,
`email`, `phone`, `website`, `address`, `province`, `country` (+ `logo`, `cover`, `name_slug` ทางอ้อม)

คอลัมน์ที่ **มีในตารางแต่แก้ไม่ได้จากที่ไหนเลยในแอป**:

| คอลัมน์ | ถูกใช้แสดงผลที่ไหน | สถานะ |
|---|---|---|
| `slogan` | `academies/index.vue:349`, `AcademyCard.vue:9`, การ์ดคำเชิญ | 🔴 แสดงอยู่ แก้ไม่ได้ |
| `established_year` | `[name].vue:2509,2593` | 🔴 แสดงอยู่ แก้ไม่ได้ |
| `donation_enabled` | `[name].vue:2463` (แผงรับบริจาค) | 🔴 คุมเงิน แก้ไม่ได้ |
| `student_editable_fields` | `app/Traits/HandlesStudentUpdates.php:58` | 🔴 บังคับใช้จริง ตั้งค่าได้เฉพาะผ่าน migration |
| `type`, `director`, `accreditation*`, `facilities`, `academy_timings`, `holidays`, `social_media_links` | AcademyResource (บางตัวขึ้นหน้าโรงเรียน) | 🔴 แก้ไม่ได้ |
| `approval_flow` | **ไม่มีใครอ่าน** | ⚫ dead column |
| `total_students`, `total_teachers`, `courses_offered` | AcademyResource | ⚠️ ตัวเลขค้าง ไม่มีใครอัปเดต |

**`academy_settings`** (คอลัมน์จริงในฐาน dev):

| คอลัมน์ | ค่าปัจจุบัน (academy 1) | มีคนอ่านไปใช้จริงไหม |
|---|---|---|
| `auto_accept_members` | 1 | ✅ `AcademyMemberController.php:75` |
| `join_mode` | `open` | ⚠️ อ่านแค่ตอนแปลงเป็น `auto_accept_members` — `invite_only` = `approval` ทุกประการ |
| `privacy` | `public` | ❌ **ไม่มีใครอ่าน** |
| `allow_student_registration` | 0 | ❌ **ไม่มีใครอ่าน** |
| `allow_parent_registration` | 0 | ❌ **ไม่มีใครอ่าน** |
| `show_member_list` | 0 | ❌ **ไม่มีใครอ่าน** |
| `show_course_list` | 0 | ❌ **ไม่มีใครอ่าน** |
| `card_request_flow_enabled` | 1 | ✅ อ่าน 5 จุด (StudentCard*Controller, StudentCardSyncService) — **แต่ไม่มี UI ตั้งค่า** |

> ⚠️ ข้อมูลจริงในฐาน dev ตอนนี้ปิด `show_member_list`/`show_course_list`/`allow_*_registration` ไว้หมด
> แต่หน้าเว็บยังแสดงรายชื่อสมาชิกและคอร์สตามปกติ — **เพราะไม่มีใครอ่านค่าพวกนี้** ยืนยันแล้วด้วยการ query

**`academies.name_slug`** = `""` (สตริงว่าง) สำหรับโรงเรียนเดียวที่มีในระบบ — เพราะ `Str::slug('ชื่อภาษาไทย')`
คืนสตริงว่าง (ทดสอบแล้ว) ดู G7

---

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | แก้ชื่อ/คำอธิบาย ไทย+อังกฤษ | ✅ | ทำงานจริง |
| 2 | อัปโหลดโลโก้/รูปปก | ⚠️ | ทำงาน แต่เก็บเป็น URL เต็ม ผูกกับโดเมน (G8) |
| 3 | ข้อมูลติดต่อ (อีเมล/โทร/เว็บ/ที่อยู่) | ✅ | ทำงานจริง |
| 4 | ตั้งค่าความเป็นส่วนตัว | ❌ | บันทึกได้ แต่ไม่มีผลกับระบบ (G3) |
| 5 | ตั้งค่าโหมดการเข้าร่วม | ⚠️ | `open` มีผล · `approval`/`invite_only` เหมือนกันทุกประการ (G4) |
| 6 | อนุญาตนักเรียน/ผู้ปกครองสมัคร | ❌ | บันทึกได้ ไม่มีผล (G3) |
| 7 | ลบโรงเรียน | ❌ | **ปุ่มตาย** ไม่มี route (G2) |
| 8 | อัตลักษณ์โรงเรียน (คำขวัญ/ปีก่อตั้ง/ประเภท/ผอ.) | ❌ | ไม่มีในฟอร์มเลย ทั้งที่โชว์บนหน้าสาธารณะ (G9) |
| 9 | สวิตช์ระบบคำร้องบัตรนักเรียน | ❌ | บังคับใช้จริง 5 จุด แต่ไม่มี UI (G9) |
| 10 | ฟิลด์ที่นักเรียนแก้เองได้ | ❌ | บังคับใช้จริง แต่ตั้งได้เฉพาะผ่าน migration (G9) |
| 11 | เปิด/ปิดการรับบริจาคของโรงเรียน | ❌ | ไม่มี UI (G9) |
| 12 | โหมดดูอย่างเดียว (`settings.view`) | ❌ | เข้าหน้าไม่ได้เลยถ้าไม่มี `settings.manage` (G6) |
| 13 | บันทึก audit log เมื่อแก้ตั้งค่า | ❌ | G11 |
| 14 | เตือนเมื่อออกจากหน้าโดยยังไม่บันทึก | ❌ | G12 |
| 15 | Tests | ❌ | 0 ไฟล์ (G10) |

---

## 4. Permission Matrix

**คีย์ที่ควรใช้:** `settings.view` / `settings.manage`
`settings.*` และ `academy.*` **ห้าม delegate ให้ฝ่าย** — ระบุไว้แล้วใน `AcademyPermission::DEPARTMENT_DELEGABLE_FAMILIES` ✅ (ไม่ต้องแก้)

| Permission | Owner | Admin | Director | ฝ่าย admin | Registrar | Teacher | Finance staff | Student | Guardian |
|---|---|---|---|---|---|---|---|---|---|
| `settings.view` | ✅ | ✅ | ✅ | ❌ (ห้าม delegate) | ❌ | ❌ | ✅ | ❌ | ❌ |
| `settings.manage` | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| ลบ/ปิดโรงเรียน | ✅ เท่านั้น | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

**ปัญหาที่พบในของจริง:**
- แคตตาล็อกสิทธิ์มี **สองชุดที่ทับกัน**: `settings.view`/`settings.manage` (กลุ่ม `settings`)
  และ `academy.settings.view`/`academy.settings.edit` (กลุ่ม `academy`)
- sidebar ใช้ `academy.settings.view || academy.settings.edit` · ตัวหน้าใช้ `settings.manage` · API ใช้ `settings.manage`
- ตอนนี้ยังไม่พังเพราะ director/admin ได้ทั้งสองชุด แต่ role ที่สร้างเองจะเห็นลิงก์แล้วเข้าไม่ได้ (หรือกลับกัน)

---

## 5. Gap Analysis

### 🔴 P0 — ความปลอดภัย

**G1. `PATCH /api/academies/{academy}/update` ไม่ตรวจสิทธิ์เลย**
`routes/learn/academy.php:141` อยู่ในกลุ่ม `auth:api` เฉย ๆ · `AcademyController::update()` ไม่เช็ค owner /
member / permission ใด ๆ → **ผู้ใช้ที่ล็อกอินคนไหนก็ได้ เปลี่ยนชื่อ คำขวัญ ที่อยู่ โลโก้ และรูปปก
ของโรงเรียนไหนก็ได้** · ไม่มีผู้เรียกใน `ui/` (grep แล้ว 0) · ยังเขียน `cover`/`logo` เป็นชื่อไฟล์เปล่า
ขัดกับ `updateSettings` ที่เขียนเป็น URL เต็ม
→ **แนะนำ: ลบ route + method ทิ้ง** (ถ้าจะเก็บ ต้องใส่ `academy.permission:settings.manage`)

**G2. โซนอันตราย "ลบโรงเรียนนี้" เป็นปุ่มตาย**
หน้าเรียก `DELETE /api/academies/{id}` แต่ `route:list` ยืนยันว่ามีแค่ `GET api/academies/{academy}`
→ ตอบ **405** · `AcademyController::destroy()` เป็นเมธอดว่าง
ผู้ใช้พิมพ์ชื่อโรงเรียนยืนยันครบทุกขั้น แล้วได้ error ดิบ ๆ
→ **ต้องให้เจ้าของโปรเจคตัดสินใจก่อน** (ดู §5.1 คำถามที่ต้องเคาะ)

### 🔴 P1 — ตั้งค่าที่บันทึกได้แต่ไม่มีผล

**G3. สวิตช์ 5 ตัวเป็น write-only** — `privacy`, `allow_student_registration`, `allow_parent_registration`,
`show_member_list`, `show_course_list` ไม่มีโค้ดบรรทัดไหนใน `app/` หรือ `ui/` อ่านค่าไปใช้เลย
(ยืนยันด้วย grep ทั้งสองฝั่ง) · ฐาน dev ปิด 4 ตัวไว้อยู่แล้วแต่ทุกอย่างยังแสดงตามปกติ
→ ผู้ดูแลเชื่อว่าปิดข้อมูลไปแล้ว ทั้งที่ยังเปิดโล่ง = **เข้าใจผิดเรื่องความเป็นส่วนตัว**

**G4. `join_mode: invite_only` ไม่ต่างจาก `approval`** — `updateSettings:558` แปลงเป็น
`auto_accept_members = 0` เหมือนกันทั้งคู่ · `AcademyMemberController:75` อ่านแค่ `auto_accept_members`
→ เลือก "เชิญเท่านั้น" แล้วคนนอกยังกดขอเข้าร่วมได้อยู่

**G5. ด่านสิทธิ์ของ `updateSettings` เขียนเองและอ่อนกว่า middleware มาตรฐาน**
`AcademyController:481-493` เช็คแค่ `$academy->user_id === auth()->id()` แล้วหา `AcademyMember` **โดยไม่กรอง
`status = 2`** → สมาชิกที่ยัง pending / ถูกปฏิเสธ / พ้นสภาพ ที่มี role ติดมา ยังบันทึกได้
· ไม่รองรับ superadmin (`Academy::isAdmin()`) · ไม่รองรับสิทธิ์ที่ได้จากฝ่าย
→ แทนที่ด้วย middleware `academy.permission:settings.manage`

### 🟠 P2 — ใช้งานได้แต่ไม่ครบ

**G6. ไม่มีโหมดดูอย่างเดียว** — ทั้งหน้าและ API ต้องมี `settings.manage` เท่านั้น
`settings.view` มีในแคตตาล็อกและ `finance_staff` ได้รับไป แต่ใช้ทำอะไรไม่ได้เลย

**G7. `name_slug` ใช้กับชื่อไทยไม่ได้ และ redirect หลังบันทึกจะพาไปหน้าเสีย**
`Str::slug()` กับชื่อไทยคืน `""` (ทดสอบแล้ว) · backfill ใน migration จึงให้โรงเรียนแรกได้ `""`
โรงเรียนถัดไปจะชนแล้วกลายเป็น `-1`, `-2` · `settings.vue:153` สั่ง
`navigateTo('/academies/{name_slug}/admin/settings')` ทั้งที่ route bind ด้วยคอลัมน์ `name`
→ ตอนนี้ยังไม่พังเพราะ `""` เป็น falsy **แต่พังทันทีที่มีโรงเรียนที่สอง**

**G8. โลโก้/ปกเก็บเป็น URL เต็ม** — `updateSettings` เขียน `Storage::disk('public')->url(...)` ลงคอลัมน์
→ ผูกกับโดเมนที่อัปโหลด ย้าย dev→production แล้วรูปหาย (สอดคล้องกับ `.agents/photo-path-migration-plan.md`)

**G9. ตั้งค่าที่มีผลจริงแต่ไม่มี UI** (เรียงตามความสำคัญ)
1. `academy_settings.card_request_flow_enabled` — บังคับใช้จริง 5 จุด คุมทั้งระบบคำร้องบัตรนักเรียน
2. `academies.student_editable_fields` — บังคับใช้จริงใน `HandlesStudentUpdates` คุมว่านักเรียนแก้อะไรเองได้
3. `academies.donation_enabled` — คุมแผงรับบริจาคของโรงเรียน
4. `slogan`, `established_year`, `type`, `director`, `social_media_links` — ขึ้นหน้าสาธารณะแต่แก้ไม่ได้
5. `approval_flow` — ไม่มีใครอ่าน → dead column ตัดทิ้งหรือทำให้ใช้งานได้

**G10. ไม่มี test เลย** — 0 ไฟล์ที่แตะ `updateSettings`/`academy_settings`

**G10b. `AcademyController` มีเมธอดตายที่ไม่มี route ชี้มา 11 ตัว** — ตรวจแล้วทั้ง `routes/` ไม่มีสักตัวที่ถูก
ประกาศเป็น route: `edit`, `create_course`, `joinAcademy`, `leaveAcademy`, `acceptMember`, `rejectMember`,
`removeMember`, `updateAcademySetting`, `updateMembershipFees`, `updateAcademyLogo`, `updateAcademyCover`
· ทั้งหมดเป็นสไตล์ web เก่า (`redirect()->back()`) และหลายตัว **ไม่มีการตรวจสิทธิ์เลย**
→ ยังไม่เป็นช่องโหว่เพราะเรียกไม่ถึง แต่เป็นระเบิดเวลาถ้ามีใครมาผูก route ให้ทีหลัง
→ แยกเป็น SET-S13 (ไม่รวมใน S1 เพื่อให้ commit ด้านความปลอดภัยอ่านง่าย)

**G11. ไม่มี audit log** — `AuditLogService` มีอยู่ เมนูอื่น (#6 ผู้ปกครอง) ใช้แล้ว
แต่การเปลี่ยนชื่อโรงเรียน/นโยบายความเป็นส่วนตัวไม่ถูกบันทึกว่าใครแก้เมื่อไหร่

**G12. UX ปลีกย่อย** — ไม่เตือนตอนออกจากหน้าโดยยังไม่บันทึก · หลังบันทึกไม่ refresh `academy.value`
(ค่าที่เห็นกับค่าที่เซิร์ฟเวอร์ normalize แล้วอาจไม่ตรงกัน) · แท็บ "โซนอันตราย" โชว์ให้คนที่ไม่ใช่ owner
เห็นทั้งแท็บแล้วค่อยบอกว่าทำไม่ได้

> **หมายเหตุ mobile-first:** สแกนหน้านี้แล้ว **ผ่านเกณฑ์** — `px-4 sm:px-0` ระดับหน้า · การ์ด `p-4 sm:p-6`
> · ปุ่มหลัก `min-h-[44px] sm:min-h-0` · แท็บ `py-3` (46px) · ไม่มีตาราง/กริดกว้าง
> ไม่มีงาน responsive ค้างในเมนูนี้

### 5.1 การตัดสินใจของเจ้าของโปรเจค (เคาะแล้ว 2026-08-28)

**D1 (ปิด Q1 — ปลด SET-S2): "ลบโรงเรียน" → เปลี่ยนเป็น "เก็บถาวร" (soft delete)**
ไม่ลบข้อมูลจริง · เพิ่ม `archived_at` บน `academies` · โรงเรียนที่เก็บถาวรถูกซ่อนจากทุกจุดที่แสดงรายการ
แต่ **กู้คืนได้** · ทำได้เฉพาะ **owner** เท่านั้น (admin/director ทำไม่ได้)
เหตุผลที่ไม่เลือกลบจริง: ฐาน dev มีผู้ปกครอง 4,504 · นักเรียน/ผลการเรียน/บัตรนักเรียนผูกกันข้าม 200+ ตาราง
การลบ cascade ย้อนกลับไม่ได้และเสี่ยงข้อมูลค้างเป็นเศษ

**D2 (ปิด Q2 — ปลด SET-S5): บังคับใช้ 3 ตัว ถอด 2 ตัว**
- **บังคับใช้จริง:** `privacy`, `show_member_list`, `show_course_list`
- **ถอดออกจากฟอร์ม:** `allow_student_registration`, `allow_parent_registration` — ซ้ำซ้อนกับ `join_mode`
  (ทั้งคู่ตอบคำถามเดียวกันว่า "ใครเข้าโรงเรียนได้บ้าง" แต่คนละที่ ทำให้ตั้งค่าขัดกันเองได้)
  → migration ล้างค่าที่ค้าง + drop คอลัมน์

**D3 (ปิด Q3): `invite_only` ต้องบล็อกการกดขอเข้าร่วมจริง**
คนนอกกดขอเข้าร่วมไม่ได้เลย เข้าได้ทางลิงก์เชิญ (เมนู #4) หรือคำเชิญตรงเท่านั้น — ให้ตรงกับชื่อโหมด
⇒ `AcademyMemberController` ต้องเลิกอ่านแค่ `auto_accept_members` แล้วอ่าน `join_mode` เป็นตัวหลัก

---

## 6. Implementation Tasks

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| **SET-S1** | อุดช่องโหว่สิทธิ์ (G1 + G5) | — | ลบ route+method `PATCH /{academy}/update` · ย้าย `POST /{academy}/settings` ไปใช้ `academy.permission:settings.manage` แล้วถอดด่านที่เขียนเองใน controller | 🟢 **verified** (ยังไม่ commit) |
| **SET-S2** | เก็บถาวรโรงเรียน แทนการลบ (G2 · D1) | SET-S1 | migration `archived_at` + `POST/DELETE /{academy}/archive` เฉพาะ owner + ซ่อนจากทุก listing + แท็บโซนอันตรายเรียกของจริง | ⚪ pending |
| **SET-S3** | รวมคีย์สิทธิ์ให้เหลือชุดเดียว (§4) | SET-S1 | ให้ sidebar/หน้า/API ใช้ `settings.view`/`settings.manage` เหมือนกัน · `academy.settings.*` เป็น alias ถอยหลังเข้ากันได้ | ⚪ pending |
| **SET-S4** | โหมดดูอย่างเดียว (G6) | SET-S3 | `settings.view` เข้าหน้าได้แบบ read-only · ปุ่มบันทึกซ่อน · API ยังกัน `settings.manage` | ⚪ pending |
| **SET-S5** | ทำให้สวิตช์มีผลจริง (G3 + G4 · D2 + D3) | SET-S1 | บังคับใช้ `privacy`/`show_member_list`/`show_course_list` · drop `allow_*_registration` 2 คอลัมน์ + ถอดออกจากฟอร์ม · `join_mode` เป็นตัวหลักแทน `auto_accept_members` และ `invite_only` บล็อกการขอเข้าร่วมจริง | ⚪ pending |
| **SET-S6** | เพิ่มแท็บ "ระบบและนโยบาย" (G9 ข้อ 1–3) | SET-S1 | `card_request_flow_enabled`, `student_editable_fields`, `donation_enabled` ตั้งค่าได้จากหน้าจอ | ⚪ pending |
| **SET-S7** | เพิ่มฟิลด์อัตลักษณ์โรงเรียน (G9 ข้อ 4–5) | SET-S6 | `slogan`, `established_year`, `type`, `director`, `social_media_links` เข้าแท็บ "ข้อมูลทั่วไป" · ตัดสินใจเรื่อง `approval_flow` | ⚪ pending |
| **SET-S8** | ซ่อม `name_slug` + redirect (G7) | SET-S1 | เลิกใช้ `Str::slug` กับชื่อไทย (fallback เป็น `academy-{id}` หรือทับศัพท์) + ตัด redirect ที่พาไป URL ผิดคีย์ | ⚪ pending |
| **SET-S9** | audit log การแก้ตั้งค่า (G11) | SET-S1 | บันทึกทุกครั้งที่ `updateSettings` เปลี่ยนค่า พร้อม before/after | ⚪ pending |
| **SET-S10** | tests (G10) | SET-S1..S6 | feature test: ด่านสิทธิ์ 403/200 · สถานะสมาชิกไม่ใช่ 2 ต้องโดนปฏิเสธ · round-trip ทุกฟิลด์ · cache ถูกล้าง | ⚪ pending |
| **SET-S11** | UX เก็บตก (G12) | SET-S4 | เตือนก่อนออกโดยไม่บันทึก · refresh หลังบันทึก · ซ่อนแท็บโซนอันตรายถ้าไม่ใช่ owner | ⚪ pending |
| **SET-S12** | รูปโลโก้/ปกเป็น relative path (G8) | — | ต่อยอดจาก `.agents/photo-path-migration-plan.md` — **แยกไปทำพร้อมงาน migration รูปทั้งระบบ** | 🔵 deferred |
| **SET-S13** | ล้างเมธอดตายใน `AcademyController` (G10b) | SET-S1 | ลบ 11 เมธอดที่ไม่มี route ชี้มา + ตรวจว่าไม่มี import ไหนหลุดค้าง | ⚪ pending |

**ลำดับที่ตกลง (Q1–Q3 เคาะครบแล้ว ไม่มี step ไหน blocked):**
SET-S1 → S3 → S4 → S5 → S2 → S8 → S6 → S7 → S9 → S11 → S10
(S1 ก่อนเพราะเป็นช่องโหว่ · S3/S4 จัดระเบียบสิทธิ์ให้เสร็จก่อนที่ S5/S2 จะไปพึ่ง · S10 tests ปิดท้ายทีเดียว)

---

## 7. Codex Prompt Template (ต่อ step)

```
Context: .agents/school-admin/07-settings.md §SET-S<n>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): <รายการ>
Task: <what codex should do>
Constraints:
  - งานใน ui/ ต้อง mobile-first (ไม่มี prefix = มือถือ, touch target >= 44px,
    ห้าม hidden ซ่อนข้อมูลสำคัญ, flex row ต้องมี flex-shrink-0 / min-w-0 flex-1)
  - ห้ามแก้ schema ตรง phpMyAdmin — ทุกอย่างเป็น migration ที่มี down() จริง
  - ./vendor/bin/pint ก่อนจบ
Verification: <build/test/manual>
Report back: git diff --stat + สรุปสิ่งที่ทำจริง
```

---

## 8. Review Log

- **2026-08-28 audit** — Claude สแกนโค้ดจริงทั้ง frontend/backend/routes/models/DB
  (query ตาราง `academies` + `academy_settings` ของจริง, `route:list` ยืนยันว่าไม่มี DELETE,
  grep ยืนยันว่าสวิตช์ 5 ตัวไม่มีผู้อ่าน) → เขียนไฟล์นี้ · เจ้าของโปรเจคเคาะ D1–D3 ครบ

- **2026-08-28 SET-S1** — agy ลงมือแก้ 2 ไฟล์ (`routes/learn/academy.php`,
  `AcademyController.php`) · **Claude ตรวจเองทุกข้อ ไม่มีตัวเลขไหนที่เชื่อรายงาน agy:**

  | ตรวจอะไร | วิธีตรวจ | ผล |
  |---|---|---|
  | ขอบเขต diff | `git diff --stat` | แตะแค่ 2 ไฟล์ที่สั่ง · +5 / −69 |
  | อ่าน diff ทุกบรรทัด | `git diff` | ตรงสเปคเป๊ะ ไม่มีบรรทัด `-` นอกที่สั่ง · pint ไม่ได้ไปจัดรูปแบบส่วนอื่นทับ |
  | route ช่องโหว่หายจริง | `route:list --path=academies \| grep` | 0 · และไม่มีการอ้าง `academy.update` เหลือทั้งเรพ |
  | middleware ติดจริง | `route:list -v` | `api → auth:api → academy.permission:settings.manage` |
  | import ไม่ถูกลบเกิน | `grep -c` | `Storage::` 10 · `AcademyMember` 4 |
  | syntax + style | `php -l` × 2, `pint --test` | ผ่านทั้งหมด |

  **ตรวจบนเซิร์ฟเวอร์จริง** (mint JWT ผ่าน tinker ยิงเข้า `POST /api/academies/1/settings`
  ด้วยชื่อโรงเรียนเดิม แล้วเช็คว่าข้อมูลไม่เปลี่ยน — `updated_at` ยังเป็น `2026-08-06 10:14:07`):

  | ผู้ยิง | ก่อนแก้ | หลังแก้ |
  |---|---|---|
  | เจ้าของโรงเรียน (user 1) | 200 | **200** ✅ ไม่มี regression |
  | นักเรียน (สมาชิก status 2 ไม่มี `settings.manage`) | 403 | **403** `Insufficient permissions` |
  | สมาชิกพ้นสภาพ (status 5) | ผ่านด่านสถานะ (ด่านเก่าไม่กรอง status) | **403** `Not a member of this academy` ✅ ตรงกับ G5 |

  ⚠️ กับดักตอนทดสอบ: ยิง `-F "name=<ชื่อไทย>"` ผ่าน Git Bash แล้วได้ **500 Malformed UTF-8**
  — เป็นปัญหาการ encode ของ shell ไม่ใช่ของโค้ด · ต้องเขียนชื่อลงไฟล์ด้วย tinker แล้วใช้
  `-F "name=<ไฟล์"` จึงได้ 200 · ถ้าเจออีกครั้งอย่าเพิ่งโทษ controller

  **ยังไม่ commit** — รอผู้ใช้สั่ง
