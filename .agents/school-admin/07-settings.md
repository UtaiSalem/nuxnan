# 07 — ตั้งค่าโรงเรียน

> ไฟล์รองของเมนู **#7 ตั้งค่าโรงเรียน** ใน [OVERVIEW.md](OVERVIEW.md)
> สถานะ: 🟢 **ปิดแล้ว 6 step — S1 · S2 · S3 · S4 · S5 (+ เมนู #1 S9)** · เหลือ S6/S7/S8/S9/S10/S11/S13

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

**G10. ~~ไม่มี test เลย~~ → มีอยู่แล้ว 5 เคส แต่ครอบไม่ครบ** *(แก้ข้อมูล 2026-08-29)*
รอบ audit แรกผมสรุปว่า "0 ไฟล์" ซึ่ง**ผิด** — grep ใช้คำว่า `updateSettings`/`academy_settings`
แต่ไฟล์จริงชื่อ `AcademySettingsUpdateTest` และอ้าง `AcademySetting` (เอกพจน์) กับ URL `/settings`
จึงไม่ match สักคำ · ของจริงคือ [`tests/Feature/Academy/AcademySettingsUpdateTest.php`](../../api/nuxnanravel/tests/Feature/Academy/AcademySettingsUpdateTest.php)
(commit `e1a12493`) ครอบ 5 เคส: owner แก้ได้ครบทุกฟิลด์ · non-owner 403 · validation 422 ·
cache ไม่ค้าง · slug ชนแล้วต่อ `-1`

**รันแล้วหลัง SET-S1 + SET-S3: ผ่านครบ 5/5 (57 assertions)** และรันทั้งโฟลเดอร์
`tests/Feature/Academy` ได้ **114 passed · 2 incomplete · 0 failed** ⇒ ไม่มี regression

สิ่งที่ยังขาด (เหลือให้ SET-S10 ทำ): เคสสิทธิ์ระดับ role (สมาชิกที่ถือ `settings.manage` ต้องผ่าน),
เคสสถานะสมาชิกไม่ใช่ APPROVED ต้องโดนปฏิเสธ (จุดที่ G5 แก้), เคส superadmin,
และเคสสิทธิ์ที่ได้จากฝ่าย/กลุ่ม — ทั้งหมดนี้ยังไม่มีเทสต์คุม

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

### 5.0 🔴 ปัญหาข้ามเมนู ที่เจอระหว่างทำ SET-S3 — ใหญ่กว่าเมนู #7 (ยังไม่แก้)

**G14. แถว `academy_roles` ในฐานกับค่าคงที่ `AcademyRole::SYSTEM_ROLES` ในโค้ด เคลื่อนออกจากกันคนละทาง**

`SYSTEM_ROLES` ถูกใช้ **ตอนสร้าง role ให้โรงเรียนใหม่เท่านั้น** ไม่มีกลไก sync ย้อนกลับ
ที่ผ่านมาการเพิ่มสิทธิ์ทำสองทางแยกกันโดยไม่นัดกัน: บางรอบเพิ่มใน migration อย่างเดียว
บางรอบเพิ่มในค่าคงที่อย่างเดียว ⇒ ตอนนี้ไม่ตรงกันทั้งคู่ วัดจากฐาน dev:

| role | ในฐานมี | ในโค้ดมี | ขาดในฐาน | เกินในฐาน |
|---|---|---|---|---|
| `director` | 31 | 42 | **19** | 8 |
| `admin` | 31 | 42 | **19** | 8 |
| `teacher` | — | — | 9 | 2 |
| `registrar` | — | — | 5 | 6 |
| `staff` | — | — | 3 | 1 |
| `finance_staff` | — | — | 2 | 1 |
| `card_admin` | **ไม่มีแถวในฐานเลย** | มีในโค้ด | — | — |

- **ขาดในฐาน** (โค้ดมี ฐานไม่มี) ของ director/admin: `roles.view`, `roles.manage`, `groups.view`,
  `groups.manage`, `staff.view`, `staff.manage`, `grades.view`, `grades.manage`, `events.view`,
  `events.manage`, `school_attendance.view`, `school_attendance.manage`, `courses.manage`,
  `schedule.view`, `students.cards.produce`, `behavior.*` 4 ตัว
  ⇒ **ถ้ามอบ role `director`/`admin` ให้ใครวันนี้ เขาจะจัดการบทบาท/ฝ่าย/บุคลากร/ผลการเรียน/
  กิจกรรม/การมาเรียน ไม่ได้เลย** ทั้งที่ชื่อ role บอกว่าได้
- **เกินในฐาน** (ฐานมี โค้ดไม่มี): `elections.*` 3 ตัว + `guardians.*` 5 ตัว — มาจาก migration
  backfill ของเมนู #25 และ #6 ที่ไม่ได้เติมกลับเข้าค่าคงที่
  ⇒ **โรงเรียนที่สร้างใหม่วันนี้ จะได้ director ที่ไม่มีสิทธิ์เลือกตั้งและผู้ปกครองเลย**
- `card_admin` มีในโค้ดแต่ไม่มีแถวในฐาน ⇒ มอบหมายไม่ได้

**ยังไม่มีใครเจอปัญหานี้** เพราะฐาน dev ไม่มีสมาชิกคนไหนถือ role `director`/`admin` เลย
(ทุกคนเป็น teacher/staff/student และเจ้าของโรงเรียนผ่านด้วย `Academy::isAdmin()` ไม่ผ่าน role)

**เป็นงานของเมนู #1 (บทบาทและสิทธิ์) ไม่ใช่ #7** — ต้องมี migration reconcile สองทาง
+ กลไกกัน drift รอบหน้า · **ยังไม่ได้แก้ รอเจ้าของโปรเจคตัดสินใจว่าจะแทรกคิวหรือไม่**

---

### 5.1 การตัดสินใจของเจ้าของโปรเจค (เคาะแล้ว 2026-08-28)

**D1 (ปิด Q1 — ปลด SET-S2): "ลบโรงเรียน" → เปลี่ยนเป็น "เก็บถาวร" (soft delete)**
ไม่ลบข้อมูลจริง · เพิ่ม `archived_at` บน `academies` · โรงเรียนที่เก็บถาวรถูกซ่อนจากทุกจุดที่แสดงรายการ
แต่ **กู้คืนได้** · ทำได้เฉพาะ **owner** เท่านั้น (admin/director ทำไม่ได้)
> 📌 **แก้ไขโดย D9 (2026-08-30):** ขยายเป็น **owner + super admin** — admin/director ยังทำไม่ได้ตามเดิม (ดู §5.5)
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

---

### 5.2 การตัดสินใจของเจ้าของโปรเจค รอบ SET-S5 (เคาะแล้ว 2026-08-30)

**D4 — `privacy = private` คือ "หน้าพรีวิว + ปุ่มขอเข้าร่วม"**
คนที่ล็อกอินแล้วแต่ไม่ใช่สมาชิก ยังเปิด `/academies/{name}` ได้ และเห็นเฉพาะ
ชื่อ · โลโก้ · ปก · คำอธิบาย · ปุ่มขอเข้าร่วม — **แท็บทั้ง 8 ถูกซ่อน** และ endpoint เนื้อหา
(feeds/activities/members/courses/groups/events/classrooms/announcements/gamification) ตอบ 403
· โรงเรียนแบบ private **ยังอยู่ในไดเรกทอรี** `/academies` ตามเดิม (ไม่ซ่อนจากการค้นหา)

**D5 — `show_member_list`/`show_course_list` = เปิด ⇒ ผู้ใช้ที่ล็อกอินทุกคนดูได้**
ตรงกับข้อความในฟอร์ม "อนุญาตให้ผู้อื่นดูรายชื่อสมาชิก" · ปิด ⇒ เหลือแค่**สมาชิกที่อนุมัติแล้ว + ผู้ดูแล**
· ถ้า `privacy = private` จะถูกจำกัดเป็นสมาชิกเท่านั้นอยู่ดี ไม่ว่าสวิตช์จะเปิดหรือปิด

สูตรที่ใช้ (เขียนเป็นเมธอดบน `Academy` ห้ามกระจายเงื่อนไขไปตาม controller):
```
canViewContent(u)    = isAdmin(u) || isApprovedMember(u) || privacy !== 'private'
canViewMemberList(u) = isAdmin(u) || isApprovedMember(u) || (canViewContent(u) && show_member_list)
canViewCourseList(u) = isAdmin(u) || isApprovedMember(u) || (canViewContent(u) && show_course_list)
```

**D6 — migration รีเซ็ต `show_member_list`/`show_course_list` เป็น `1` ให้ทุกแถวที่มีอยู่**
ค่า `0` ที่ค้างในฐาน dev ถูกตั้งตอนที่สวิตช์ยังไม่มีผล = ไม่ใช่เจตนาจริงของผู้ดูแล
ถ้าไม่รีเซ็ต พอ deploy แล้วรายชื่อสมาชิกกับคอร์สของโรงเรียนเดียวในระบบจะหายจากสายตาคนนอกทันที
**ข้อจำกัด:** `down()` คืนค่าเดิมไม่ได้ (ค่าเก่าไม่ถูกเก็บไว้) — ต้องเขียนบอกไว้ในหัวไฟล์ migration

**D7 — `academy_settings.auto_accept_members` drop ทิ้ง** — `join_mode` เป็นแหล่งความจริงเดียว
(`course_settings.auto_accept_members` เป็นคนละคอลัมน์ คนละตาราง **ไม่แตะ**)

---

### 5.3 🔴 สองบั๊กที่เจอตอน audit SET-S5 (ยืนยันกับของจริงแล้ว)

**G15. `join_mode: open` พังมาตั้งแต่ 2026-07-10 — คำขอเข้าร่วมค้าง pending ทุกใบ**

`AcademyMemberController.php:75` เขียนว่า
```php
if ($academy->academySetting->auto_accept_members === 1) {
```
แต่ `AcademySetting::$casts` แปลง `auto_accept_members` เป็น `boolean` (commit `59af6c73`, 2026-07-10)
⇒ ค่าที่ได้คือ `true` ไม่ใช่ `1` ⇒ `true === 1` เป็น **false เสมอ** ⇒ ตกเข้า else ทุกครั้ง

ยืนยันกับฐาน dev ด้วย tinker:
```
academy 1: join_mode=open · auto_accept_members=true · (auto_accept_members === 1) => false
```
⇒ โรงเรียนที่ตั้งเป็น "เปิดรับสมัคร" ทุกคำขอกลายเป็น **สถานะ 1 (รออนุมัติ)** และ
`total_students` ไม่เคยเพิ่ม · **ไม่ใช่แค่ `invite_only` ที่ตาย — `open` ก็ตายด้วย**
เอกสาร audit รอบแรกที่เขียนว่า "`open` มีผล" **ผิด** (แก้ข้อมูลแล้ว ณ 2026-08-30)

**G16. `GET /api/academies/{academy}/members` ไม่มีด่านสิทธิ์เลยสักชั้น**

`routes/learn/academy.php:180` → `getAcademyMembers()` (บรรทัด 455) ไม่เช็คสมาชิกภาพ ไม่เช็ค permission
และ `AcademyMemberResource` คืน `user.email`, `student.full_name_th`, `student.gender`,
`student.current_classroom`, `member_code`
⇒ **ผู้ใช้ที่ล็อกอินคนไหนก็ได้ ไล่อ่านรายชื่อนักเรียนพร้อม PII ของโรงเรียนไหนก็ได้**
(ฐาน dev มีนักเรียน 4,500+) · นี่คือสิ่งที่ `show_member_list` ควรจะกันมาตลอด
⇒ ยกระดับ G3 จาก "สวิตช์ไม่มีผล" เป็น **ช่องโหว่ความเป็นส่วนตัว P1**

**G17. `AcademyController::store()` เขียน `auto_accept_members` แต่ไม่เขียน `join_mode`**

โรงเรียนที่สร้างใหม่โดยเลือก "ต้องอนุมัติ" จะได้ `auto_accept_members = 0` แต่ `join_mode = 'open'`
(ค่า default ของคอลัมน์) ⇒ พอ `join_mode` กลายเป็นแหล่งความจริงเดียวใน S5
โรงเรียนนั้นจะ**พลิกเป็นเปิดรับสมัครเงียบ ๆ** ⇒ migration ต้อง reconcile ก่อน drop:
**แถวไหนสองค่าไม่ตรงกัน ให้เชื่อ `auto_accept_members`** (เพราะ `updateSettings` เขียนสองค่าพร้อมกันเสมอ
แถวที่ขัดกันจึงมาจาก `store()` เท่านั้น)

**G18. endpoint อื่นของโรงเรียนที่ยังไม่มีด่านสมาชิกภาพ (เจอตอนตรวจเบราว์เซอร์ S5 — ยังไม่แก้)**

ตอนเปิดหน้าโรงเรียนแบบ private ด้วยบัญชีคนนอก endpoint พวกนี้ยังตอบ **200**:
`/{academy}/school-attendances` · `/{academy}/emergency-alerts/active` · `/{academy}/revenue/support-summary` · `/{academy}/my-role`
ตรวจ payload จริงแล้ว **รอบนี้ยังไม่รั่วข้อมูล** (ว่างทั้งหมด เพราะไม่มีคาบเช็กชื่อเปิดอยู่/ไม่มีประกาศฉุกเฉิน/ยอดบริจาคเป็น 0)
แต่ `school-attendances` ไม่มีการกรองสมาชิกภาพเลย ⇒ ถ้ามีคาบเปิดอยู่ คนนอกจะเห็น
→ ไม่รวมใน S5 (คนละโดเมน) · ควรเปิดเป็นงานของเมนู #9/#19 หรือ SET-S14

---

### 5.4 SET-S5 — แผนแตก shard (6 shard)

| shard | สาระ | ไฟล์ | ขึ้นกับ |
|---|---|---|---|
| **S5-A** | แกนกลางการมองเห็น — เมธอดบน `Academy` + middleware `academy.visibility` | `app/Models/Academy.php`, `app/Http/Middleware/EnsureAcademyVisibility.php` (ใหม่), `bootstrap/app.php` | — |
| **S5-B** | ติดด่านกับ route ที่หน้าโรงเรียนเรียกจริง | `routes/learn/academy.php` | S5-A |
| **S5-C** | `join_mode` เป็นตัวหลัก + `invite_only` บล็อกจริง + ซ่อม G15/G17 | `AcademyMemberController.php`, `AcademyController.php`, `AcademyResource.php` | S5-A |
| **S5-D** | migration: reconcile → reset show_* → drop 3 คอลัมน์ | migration ใหม่ 1 ไฟล์, `AcademySetting.php` | S5-C |
| **S5-E** | frontend: ถอด 2 สวิตช์ · ซ่อนแท็บ · หน้าพรีวิว private · ปุ่มเข้าร่วมใต้ `invite_only` | `admin/settings.vue`, `[name].vue`, `useAcademyNavigation.ts`, `SchoolQuickMenu.vue` | S5-C |
| **S5-F** | เทสต์: แก้ของเดิม + `AcademyJoinModeTest` + `AcademyVisibilityTest` | `tests/Feature/Academy/` | S5-B..E |

**ลำดับ:** A → C → D → B → E → F (A/C ขนานกับ E ไม่ได้เพราะ E พึ่ง field ใหม่ใน resource)

### 5.5 การตัดสินใจของเจ้าของโปรเจค รอบ SET-S2 (เคาะแล้ว 2026-08-30)

**D8 — ซ่อนด้วยการกรองเฉพาะจุดที่ list + ด่านที่หน้าโรงเรียน (ไม่ใช้ global scope / SoftDeletes)**
เหตุผล: มี **95 โมเดลที่ `belongsTo(Academy::class)`** และ **93 จุดที่ eager-load `academy`**
ถ้าใส่ global scope (หรือ `SoftDeletes`) ความสัมพันธ์พวกนี้จะกลายเป็น `null` ทั้งระบบทันทีที่โรงเรียนถูกเก็บถาวร
— gradebook / transcript / บัตรนักเรียน / บริจาค / คอร์ส จะพังในจุดที่ไม่เกี่ยวกับเมนู #7 เลย
⇒ ใช้ `scopeNotArchived()` แปะที่ **5 จุด listing ที่นับได้จริง** + ด่าน middleware ที่หน้าโรงเรียน
(ตรวจครบทุกจุดได้ด้วย grep · ไม่กระทบ relation)

**D9 — เก็บถาวร/กู้คืนได้: เจ้าของ + super admin**
ต่างจากถ้อยคำเดิมของ D1 ที่เขียนว่า "owner เท่านั้น" — เผื่อกรณีเจ้าของหายไปหรือบัญชีถูกปิด
ให้ผู้ดูแลระบบกู้คืนแทนได้ · **admin/director ของโรงเรียนยังทำไม่ได้** ตามเดิม

**D10 — เจ้าของกู้คืนจากหน้า `/academies` ส่วน "เก็บถาวรแล้ว"**
⚠️ **ข้อเท็จจริงที่ต้องรู้:** เจ้าของโรงเรียน **ไม่มีแถวใน `academy_members`** (ยืนยันกับ dev: academy 1
เจ้าของคือ user 1 · member row = 0) ⇒ แท็บ "โรงเรียนของฉัน" ปัจจุบันที่ยิง `membered-academies`
**ไม่เคยแสดงโรงเรียนที่ตัวเองเป็นเจ้าของอยู่แล้ว** ⇒ ห้ามต่อยอดจากแท็บนั้น
ต้องมี **endpoint ใหม่ `GET /api/academies/archived`** แยกต่างหาก

**D11 — คอร์สใต้โรงเรียนที่ถูกเก็บถาวร ไม่ถูกแตะ**
คอร์สยังเปิดเรียนได้ตามปกติ · ผู้เรียนที่กำลังเรียนอยู่ไม่สะดุด · SET-S2 ซ่อนแค่ "ตัวโรงเรียน"

---

### 5.6 SET-S2 — แผนแตก shard (5 shard)

| shard | สาระ | ไฟล์ | ขึ้นกับ |
|---|---|---|---|
| **S2-A** | คอลัมน์ + แกนกลางบนโมเดล | migration ใหม่ 1 ไฟล์, `app/Models/Academy.php`, `app/Http/Middleware/EnsureAcademyVisibility.php` | — |
| **S2-B** | endpoint เก็บถาวร/กู้คืน/รายการที่เก็บถาวร | `AcademyController.php`, `routes/learn/academy.php` | S2-A |
| **S2-C** | กรองออกจาก 5 จุด listing + payload | `AcademyController.php`, `CampaignController.php`, `AcademyResource.php` | S2-B |
| **S2-D** | frontend: แท็บโซนอันตราย + ส่วน "เก็บถาวรแล้ว" + แถบเตือนบนหน้าโรงเรียน | `admin/settings.vue`, `academies/index.vue`, `academies/[name].vue` | S2-C |
| **S2-E** | เทสต์ `AcademyArchiveTest` | `tests/Feature/Academy/` | S2-A..D |

**ลำดับ:** A → B → C → D → E (B กับ C แตะ `AcademyController` ไฟล์เดียวกัน **ห้ามรันขนาน**)

#### S2-A — คอลัมน์ + แกนกลาง

- migration `add_archived_at_to_academies_table` — `timestamp('archived_at')->nullable()->index()`
  · `up()` guard ด้วย `Schema::hasColumn` · `down()` drop จริง (ตัดคอลัมน์ทิ้ง ข้อมูล archived_at หายเป็นเรื่องปกติ)
  · **ไม่ต้องล้างแคช** — `academy_settings_{id}` เก็บแถวของตาราง `academy_settings` ไม่ใช่ `academies`
    (ต่างจากกับดักของ SET-S5) แต่ให้ยืนยันด้วยการอ่าน `getSettings()` ก่อนสรุป
- `Academy.php`
  - `$casts` += `'archived_at' => 'datetime'` · **ห้ามใส่ `$fillable`** (ป้องกัน mass assignment ผ่าน `updateSettings`)
  - `scopeNotArchived($q)` = `whereNull('archived_at')` · `scopeArchived($q)` = `whereNotNull('archived_at')`
  - `isArchived(): bool`
  - `canManageArchive($user): bool` = `$user && ($user->isSuperAdmin() || $this->user_id === $user->id)`
    **นิยามเดียวเท่านั้น** — ห้ามเขียนเงื่อนไขนี้ซ้ำใน controller/middleware/route (บทเรียนจาก commit `d1b54b29`)
  - `canViewContent($user)` — เพิ่มด่านแรกสุด: ถ้า `isArchived()` และ `! canManageArchive($user)` ⇒ `false`
    (ทำให้ `AcademyResource` ตัด payload ให้คนนอกโดยอัตโนมัติ และ 11 route ที่ถือ `academy.visibility:content` ปิดตามทันที)
- `EnsureAcademyVisibility` — เช็ค `isArchived()` **ก่อน** match aspect แล้วคืน
  `403 {code: 'academy_archived'}` ข้อความ "โรงเรียนนี้ถูกเก็บถาวรแล้ว" ถ้าผู้เรียกไม่ผ่าน `canManageArchive`
  (ต้องแยก code จาก `academy_private` ไม่งั้น frontend แยกสองสถานะไม่ออก)

#### S2-B — endpoint

| method | path | handler | สิทธิ์ | ผลลัพธ์ |
|---|---|---|---|---|
| `POST` | `/api/academies/{academy}/archive` | `AcademyController::archive` | `canManageArchive` | 200 · ตั้ง `archived_at = now()` · **409 ถ้าเก็บถาวรอยู่แล้ว** |
| `DELETE` | `/api/academies/{academy}/archive` | `AcademyController::restore` | `canManageArchive` | 200 · `archived_at = null` · **409 ถ้าไม่ได้ถูกเก็บถาวร** |
| `GET` | `/api/academies/archived` | `AcademyController::archivedIndex` | ผู้ใช้ที่ล็อกอิน | คืนเฉพาะโรงเรียนที่ **ตัวเองเป็นเจ้าของ** และถูกเก็บถาวร · super admin เห็นทั้งหมด |

- 🔴 **`/archived` ต้องประกาศก่อน `Route::get('/{academy:name}')`** ไม่งั้นจะถูก wildcard กลืน
  — วางไว้กลุ่มเดียวกับ `/all-academies` ที่มีคอมเมนต์ `Specific routes MUST come before wildcard routes` อยู่แล้ว
- ไม่ต้องเขียน middleware ใหม่ — เรียก `$academy->canManageArchive($request->user())` ในเมธอด แล้ว 403
- เขียนผ่าน `$academy->update([...])` **ไม่ใช่ `DB::table()`** เพื่อให้ trait `Auditable` (มีอยู่บนโมเดลแล้ว)
  บันทึก audit log ให้อัตโนมัติ ⇒ SET-S2 ไม่ต้องแตะ `AuditLogService` เอง
  · หมายเหตุ: `archived_at` ไม่อยู่ใน `$fillable` ⇒ ต้องเซ็ตด้วย `$academy->archived_at = ...; $academy->save();`
- ลบ `AcademyController::destroy()` (เมธอดว่าง ไม่มี route ชี้มา) ทิ้งไปพร้อมกัน

#### S2-C — 5 จุดที่ต้องกรอง (ครบทั้งหมดที่มีในโค้ด ณ 2026-08-30)

| # | ที่ | เดิม |
|---|---|---|
| 1 | `AcademyController::getAllAcademies()` | `Academy::paginate(10)` |
| 2 | `AcademyController::getAuthMemberedAcademies()` | `Academy::whereIn('id', $academyIds)->paginate(10)` |
| 3 | `AcademyController::getMyAcademies()` | `auth()->user()->academies()->paginate(10)` |
| 4 | `AcademyController::searchAcademies()` | `Academy::where('name','like',…)->get()` |
| 5 | `CampaignController::targetAcademies()` | `Academy::query()->select(…)` |

ทุกจุดเติม `->notArchived()` · `AcademyResource` เพิ่ม 2 คีย์ใน `$base` (ต้องอยู่ใน `$base` ไม่ใช่ท่อนล่าง
เพราะคนนอกที่เจอโรงเรียนเก็บถาวรจะได้แค่ `$base`): `is_archived`, `archived_at`

#### S2-D — frontend (mobile-first บังคับ)

- `admin/settings.vue` — แท็บ "โซนอันตราย" เปลี่ยนจากลบเป็นเก็บถาวร
  · หัวข้อ "เก็บถาวรโรงเรียน" · อธิบายว่า **ข้อมูลไม่ถูกลบ กู้คืนได้ทุกเมื่อ** และจะหายจากรายการค้นหา/ไดเรกทอรี
  · คงขั้นตอนพิมพ์ชื่อยืนยันไว้ (Swal `input` เดิม) แต่เปลี่ยนคำ
  · `confirmArchiveAcademy()` → `POST /api/academies/{id}/archive` → `navigateTo('/academies?view=archived')`
  · ถ้า `academy.is_archived` ⇒ แสดงกล่อง "โรงเรียนนี้ถูกเก็บถาวรอยู่" + ปุ่ม **กู้คืน** (`DELETE .../archive`) แทน
  · ปุ่มยังคง `v-if="isOwner"` เดิม (super admin ที่ไม่ใช่เจ้าของกู้คืนผ่านหน้า `/academies` ได้อยู่แล้ว)
- `academies/index.vue` — เพิ่ม view ที่สาม `'archived'`
  · ยิง `GET /api/academies/archived` ตอน mount (เงียบ ๆ) — **โชว์ปุ่มสลับ view นี้ก็ต่อเมื่อผลลัพธ์ > 0**
    (ไม่งั้นผู้ใช้ทั่วไปเห็นแท็บว่างที่ไม่มีความหมาย)
  · การ์ดในโหมดนี้ติดป้าย "เก็บถาวรแล้ว" + ปุ่ม "กู้คืน"
- `academies/[name].vue` — ถ้า `academy.is_archived` และผู้ดูมีสิทธิ์ ⇒ แถบเตือนบนสุด
  "โรงเรียนนี้ถูกเก็บถาวร — ผู้อื่นมองไม่เห็น" + ลิงก์ไปแท็บโซนอันตราย

> **กติกา mobile-first (บังคับทุกไฟล์ใน `ui/`)** — class ไม่มี prefix = มือถือ แล้วค่อย `sm:`/`md:`
> · ห้าม `hidden` ซ่อนข้อมูลสำคัญบนมือถือ · touch target ≥ 44px (`min-h-[44px] sm:min-h-0`)
> · ทุกแถว flex: ฝั่งห้ามบีบ `flex-shrink-0 whitespace-nowrap` / ฝั่งข้อความ `min-w-0 flex-1 break-words`
> · `p-3 sm:p-6`, `text-sm sm:text-base` · ตรวจจริงที่ 375px ก่อน

#### S2-E — เทสต์ `tests/Feature/Academy/AcademyArchiveTest.php`

1. เจ้าของกดเก็บถาวร ⇒ 200 · `archived_at` ไม่เป็น null
2. super admin ที่ไม่ใช่เจ้าของ ⇒ 200
3. สมาชิกที่ถือ role admin ของโรงเรียน ⇒ **403** (เน้นว่า `settings.manage` ไม่พอ)
4. คนนอก ⇒ 403
5. กดซ้ำตอนเก็บถาวรอยู่แล้ว ⇒ 409 · กู้คืนตอนไม่ได้ถูกเก็บ ⇒ 409
6. โรงเรียนที่เก็บถาวรหายจาก `all-academies` · `membered-academies` · `search` ครบทั้งสาม
7. คนนอกยิง endpoint เนื้อหาของโรงเรียนที่เก็บถาวร ⇒ 403 code `academy_archived` (ไม่ใช่ `academy_private`)
8. เจ้าของยิง endpoint เดียวกัน ⇒ 200 (เข้าไปกู้คืนได้)
9. `GET /archived` คืนเฉพาะของตัวเอง — ผู้ใช้อีกคนที่มีโรงเรียนเก็บถาวรของตัวเองต้องไม่เห็นข้ามกัน
10. กู้คืนแล้วกลับมาโผล่ใน `all-academies` อีกครั้ง

⚠️ **กับดักตอนตรวจใน dev:** user 1 เป็นทั้ง **เจ้าของ academy 1 และ `SUPER_ADMIN`** พร้อมกัน
⇒ ยิง API ด้วย user 1 พิสูจน์ D9 ไม่ได้เลย ต้องใช้บัญชีที่สามที่เป็น super admin แต่ไม่ใช่เจ้าของ
(หรือสร้างในเทสต์) และบัญชีที่เป็น admin ของโรงเรียนแต่ไม่ใช่เจ้าของ เพื่อพิสูจน์ข้อ 3

---

## 6. Implementation Tasks

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| **SET-S1** | อุดช่องโหว่สิทธิ์ (G1 + G5) | — | ลบ route+method `PATCH /{academy}/update` · ย้าย `POST /{academy}/settings` ไปใช้ `academy.permission:settings.manage` แล้วถอดด่านที่เขียนเองใน controller | 🟢 **verified** (ยังไม่ commit) |
| **SET-S2** | เก็บถาวรโรงเรียน แทนการลบ (G2 · D1 + D8–D11) | SET-S1 | migration `archived_at` + `POST/DELETE /{academy}/archive` + `GET /academies/archived` (เจ้าของ + super admin ตาม D9) + กรอง 5 จุด listing + แท็บโซนอันตรายเรียกของจริง + ส่วน "เก็บถาวรแล้ว" บน `/academies` | 🟢 **verified + migrate แล้ว** |
| **SET-S3** | รวมคีย์สิทธิ์ให้เหลือชุดเดียว (§4) | SET-S1 | ถอด `academy.settings.view/edit` ออกจากแคตตาล็อก+SYSTEM_ROLES · ย้าย 6 จุดใน FE มาใช้ `settings.*` · migration ย้ายคีย์ในแถว `academy_roles` จริง | 🟢 **verified + migrate แล้ว** |
| **SET-S4** | โหมดดูอย่างเดียว (G6) | SET-S3 | `settings.view` เข้าหน้าได้แบบ read-only · ปุ่มบันทึก+ปุ่มอัปโหลดซ่อน · 16 ช่อง disabled · แถบแจ้งเตือน · แท็บโซนอันตรายเฉพาะเจ้าของ · API ยังกัน `settings.manage` | 🟢 **verified** |
| **SET-S5** | ทำให้สวิตช์มีผลจริง (G3 + G4 · D2 + D3) | SET-S1 | บังคับใช้ `privacy`/`show_member_list`/`show_course_list` · drop `allow_*_registration` 2 คอลัมน์ + ถอดออกจากฟอร์ม · `join_mode` เป็นตัวหลักแทน `auto_accept_members` และ `invite_only` บล็อกการขอเข้าร่วมจริง | 🟢 **verified + migrate แล้ว** |
| **SET-S6** | เพิ่มแท็บ "ระบบและนโยบาย" (G9 ข้อ 1–3) | SET-S1 | `card_request_flow_enabled`, `student_editable_fields`, `donation_enabled` ตั้งค่าได้จากหน้าจอ | ⚪ pending |
| **SET-S7** | เพิ่มฟิลด์อัตลักษณ์โรงเรียน (G9 ข้อ 4–5) | SET-S6 | `slogan`, `established_year`, `type`, `director`, `social_media_links` เข้าแท็บ "ข้อมูลทั่วไป" · ตัดสินใจเรื่อง `approval_flow` | ⚪ pending |
| **SET-S8** | ซ่อม `name_slug` + redirect (G7) | SET-S1 | เลิกใช้ `Str::slug` กับชื่อไทย (fallback เป็น `academy-{id}` หรือทับศัพท์) + ตัด redirect ที่พาไป URL ผิดคีย์ | ⚪ pending |
| **SET-S9** | audit log การแก้ตั้งค่า (G11) | SET-S1 | บันทึกทุกครั้งที่ `updateSettings` เปลี่ยนค่า พร้อม before/after | ⚪ pending |
| **SET-S10** | เติมเทสต์ที่ยังขาด (G10) | SET-S1..S6 | ต่อยอด `AcademySettingsUpdateTest` ที่มีอยู่แล้ว (round-trip/validation/cache/slug ครอบแล้ว) — เพิ่ม: role ที่ถือ `settings.manage` ต้องผ่าน · สมาชิกสถานะไม่ใช่ APPROVED ต้องโดนปฏิเสธ · superadmin · สิทธิ์จากฝ่าย/กลุ่ม | ⚪ pending |
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

  commit: `d33af5f5` (โค้ด) · `9753c096` (เอกสาร)

- **2026-08-29 SET-S3** — agy แก้ 7 ไฟล์ + สร้าง migration
  `2026_08_29_000001_unify_settings_permission_keys.php` · **Claude ตรวจเองทุกข้อ:**

  | ตรวจอะไร | วิธีตรวจ | ผล |
  |---|---|---|
  | ขอบเขต diff | `git status` + `git diff --stat` | ตรงสเปค 7 ไฟล์ + migration 1 ไฟล์ ไม่มีไฟล์แปลกปลอม |
  | อ่าน diff ทุกบรรทัด | `git diff` | เป็นการแก้เงื่อนไขล้วน ไม่มี markup/Tailwind ถูกแตะเลย |
  | คีย์เก่าหมดจากโค้ด | `grep -rn "academy\.settings\.\(view\|edit\)"` | เหลือ 3 จุด **อยู่ในตัว migration เองทั้งหมด** (ต้องมี) · โค้ดแอป + UI = 0 |
  | ชื่อ route ไม่โดนลูกหลง | `grep -c "api.academy.settings.update"` | 1 |
  | syntax + style | `php -l` × 3, `pint --test` | ผ่าน |
  | SFC compile | `@vue/compiler-sfc` 3 ไฟล์ | OK ทั้ง 3 |
  | **รัน migration จริงบน dev** | `migrate` → ตรวจแถว | `director`/`admin` เปลี่ยนจาก `academy.settings.view,edit` เป็น `settings.view,settings.manage` · role ที่ยังถือคีย์เก่า = **0** |
  | **ทดสอบ `down()` จริง** | `migrate:rollback --step=1` → ตรวจแถว → `migrate` ใหม่ | คืนคีย์เก่ากลับได้ตามที่ docblock ระบุ (คืนแบบ superset ไม่ใช่ย้อน byte ต่อ byte) |
  | **ตรวจ end-to-end บนเซิร์ฟเวอร์จริง** | ตั้ง member id 1 (user 2 — **ไม่ใช่เจ้าของ ไม่ใช่ academyAdmin ไม่ใช่ superadmin**) เป็น role `admin` ชั่วคราว แล้วยิง `POST /api/academies/1/settings` | **200** ⇒ ผ่านด้วยสิทธิ์จาก role ล้วน ๆ · คืนค่า `academy_role_id` กลับเป็น staff (5) แล้ว · `academies.updated_at` ยังเป็น `2026-08-06 10:14:07` ไม่ถูกแตะ |

  ระหว่างตรวจเจอ **G14 (ดู §5.0)** — แถว `academy_roles` กับค่าคงที่ `SYSTEM_ROLES` เคลื่อนออกจากกัน
  คนละทาง เป็นปัญหาของเมนู #1 ไม่ใช่ #7 · **บันทึกไว้ ยังไม่แก้**

- **2026-08-29 SET-S4** — agy แก้ 2 ไฟล์ (`admin/settings.vue`, `admin.vue`) · **Claude ตรวจเองทุกข้อ:**

  | ตรวจอะไร | วิธีตรวจ | ผล |
  |---|---|---|
  | ขอบเขต diff | `git diff --stat` | 2 ไฟล์ตามสเปค · +74 / −35 · ไม่มีบรรทัด `-` นอกขอบเขต |
  | ช่องกรอกถูกล็อกครบ | `grep -c 'v-model="form\.'` เทียบ `grep -c ':disabled="isReadOnly"'` | **16 = 16** (ข้อความ 10 · radio 2 · checkbox 4) |
  | ด่านเก่าหาย / computed ใหม่มี | `grep -c` | ด่านเก่า 0 · `canManage` 1 · `isReadOnly` 1 · `v-if="canManage"` 3 (ปุ่มบันทึก + กล้อง 2) |
  | เมนูใน `admin.vue` แก้ถูกตัว | `grep -c` | `settings.view \|\| settings.manage` 1 (ตั้งค่าโรงเรียน) · `settings.manage` เดี่ยวเหลือ 1 (ระบบบริหารโรงเรียน — ถูกต้อง ไม่ควรแตะ) |
  | SFC compile | `@vue/compiler-sfc` 2 ไฟล์ | OK ทั้งคู่ |
  | **ยิงจริงด้วยสมาชิกที่มีแค่ `settings.view`** | ตั้ง member id 1 (user 2 ไม่ใช่เจ้าของ) เป็น role `finance_staff` ชั่วคราว | `GET /api/academies/{ชื่อ}` **200** (`myRole.permissions` = `settings.view`) ⇒ เข้าหน้าได้ · `GET /my-role` **200** · **`POST /settings` = 403 `Insufficient permissions`** ⇒ ปลดล็อกหน้าจอแล้วแต่ API ยังกันอยู่ (defense in depth) |
  | ข้อมูล dev ไม่ถูกแตะ | ตรวจหลังคืนค่า | member 1 กลับเป็น `staff` · `academies.updated_at` ยังเป็น `2026-08-06 10:14:07` |

  **Claude แก้เองหลัง agy 1 จุด:** แถบแจ้งเตือนโหมดอ่านอย่างเดียวย่อหน้าเกินไป 2 ช่อง
  (8 แทนที่จะเป็น 6) ไม่ตรงระดับกับพี่น้องในบล็อกเดียวกัน — จัดย่อหน้าให้ตรงแล้ว

  ⚠️ **ยังไม่ได้ยืนยันด้วยตาบนหน้าจอจริง** — dev server ไม่ได้รันอยู่ และการจะเห็นโหมด
  อ่านอย่างเดียวต้องล็อกอินเป็นผู้ใช้ที่มีแค่ `settings.view` ซึ่งต้องใช้รหัสผ่านของคนนั้น
  ที่พิสูจน์แล้วคือ **ตรรกะ + สัญญาของ API** ส่วนการเรนเดอร์ (แถบเหลืองขึ้นจริง ช่องเป็นสีจาง
  ปุ่มกล้องหาย) ยังเป็นหนี้ตรวจอยู่ — จุดเดียวกับที่งานเมนู #6 เคยเจอว่าเทสต์จับบั๊ก UI ไม่ได้
