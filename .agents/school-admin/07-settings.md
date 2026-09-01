# 07 — ตั้งค่าโรงเรียน

> ไฟล์รองของเมนู **#7 ตั้งค่าโรงเรียน** ใน [OVERVIEW.md](OVERVIEW.md)
> สถานะ: 🟢 **ปิดแล้ว 8 step — S1 · S2 · S3 · S4 · S5 · S6 · S7 · S8** · เหลือ S9/S10/S11/S13

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

### 5.7 การตัดสินใจของเจ้าของโปรเจค รอบ SET-S8 (เคาะแล้ว 2026-08-30)

**D12 — `academies.name_slug` ลบทิ้งทั้งคอลัมน์ ไม่ซ่อม**
audit เดิม (G7) เสนอให้ทำ slug ให้ใช้งานได้ (fallback `academy-{id}` หรือทับศัพท์)
แต่พอวัดของจริงแล้ว **ไม่มีอะไรคุ้มให้ซ่อม**:

| ข้อเท็จจริงที่วัดได้ | ผล |
|---|---|
| `academies.name` มี **UNIQUE index** อยู่แล้ว (`SHOW INDEX`) | route ด้วยชื่อไทยตรง ๆ ไม่มีทางชนกัน |
| `ui/` route ด้วย `academyName` (ชื่อดิบ) **130+ จุด** | ไม่มีใครใช้ slug นำทาง |
| grep `name_slug` ทั้ง `app/` `routes/` `resources/` `config/` | เจอแค่ 3 จุด — **เขียน** อย่างเดียว + ส่งใน payload |
| ผู้อ่านเพียงรายเดียวคือ redirect ใน `settings.vue` | ซึ่งเป็นตัวที่ **พัง** (ส่ง slug เข้า route ที่ผูกด้วย `name`) |
| คอลัมน์ประกาศ `->index()` ใน migration 2026-07-10 | แต่ `SHOW INDEX` **ไม่มี index ตัวนี้จริง** — สาขา `hasColumn` ไม่เคยวิ่ง |

⇒ คอลัมน์นี้เป็น **dead column ที่มีต้นทุน** (สร้างค่าผิดทุกครั้งที่ชื่อเป็นไทย) ⇒ ตัดทิ้ง

**หลักฐานที่ยืนยันว่าค่ามันผิดจริง (tinker บนฐาน dev):**
```
Str::slug('โรงเรียนจริยธรรมศึกษามูลนิธิ')  =>  ''          ← โรงเรียนเดียวในฐานได้ค่านี้
Str::slug('โรงเรียน ABC 2569')            =>  'abc-2569'  ← ตัดภาษาไทยทิ้งเงียบ ๆ
```
ข้อสองแย่กว่าที่ audit รอบแรกบันทึกไว้ — ไม่ใช่แค่ "คืนค่าว่าง" แต่ **กลืนส่วนภาษาไทยหายไป**

---

### 5.8 🔴 บั๊กที่เจอตอน audit SET-S8 (ยืนยันกับโค้ดจริงแล้ว)

**G19. เปลี่ยนชื่อโรงเรียนไปชนชื่อที่มีอยู่ ⇒ 500 พร้อม SQL error ดิบหลุดถึงผู้ใช้**

`updateSettings()` validate ว่า `'name' => 'required|string|max:255'` — **ไม่มีกฎ `unique`**
แต่ `academies.name` เป็น UNIQUE index ในฐาน ⇒ `save()` โยน `QueryException` (SQLSTATE 23000)
แล้วถูก `catch (\Throwable $th)` ตอบกลับเป็น `500` พร้อม `$th->getMessage()`
⇒ ผู้ใช้เห็นข้อความ SQL ดิบ ๆ แทนที่จะเป็น 422 "ชื่อนี้ถูกใช้แล้ว"

`store()` ก็มีช่องเดียวกัน (`'name' => 'required|string'` ไม่มี unique) แต่ `catch` ของมัน
`throw $th` ต่อ ⇒ 500 เหมือนกัน

**ทำไมถึงไม่มีใครเจอ:** loop กันชนของ slug ทำงาน**ผิดคอลัมน์** — มันไล่หาชนบน `name_slug`
ไม่ใช่ `name` ⇒ แถวที่ชนจริงคือ `name` ไม่เคยถูกกัน · และฐาน dev มีโรงเรียนเดียว
⇒ **การลบ slug ทิ้งต้องมาพร้อมการย้ายด่านกันชนไปไว้ที่ `name` ให้ถูกที่**
(เทสต์เดิม `test_name_slug_regenerates_and_avoids_collision` เขียนคอมเมนต์เตือนกับดักนี้ไว้เองว่า
*"But names must be unique due to DB unique constraint on name"* แล้ว assert ผิดตัว)

**G20. redirect หลังเปลี่ยนชื่อไม่ได้แค่ "ผิดคีย์" — มันจำเป็น แต่ชี้ผิดที่**

`settings.vue` เดิม:
```js
if (response.academy?.name_slug && response.academy.name_slug !== academyName.value) {
  navigateTo(`/academies/${response.academy.name_slug}/admin/settings`)
}
```
route param `[name]` ผูกกับคอลัมน์ `name` ⇒ ส่ง slug เข้าไปจะได้ 404
**แต่จะตัดทิ้งเฉย ๆ ไม่ได้** — เพราะถ้าเจ้าของเปลี่ยนชื่อโรงเรียนแล้วไม่ redirect
URL ปัจจุบันจะค้างอยู่ที่ชื่อเก่า และ refresh ครั้งถัดไปได้ 404 ทันที
⇒ ต้องเปลี่ยนไปใช้ `name` + `encodeURIComponent` (แพตเทิร์นเดียวกับ `academies/index.vue` ที่ใช้อยู่แล้ว)

---

### 5.9 🔴 กับดักที่เจอตอน implement SET-S8 — ฐานสองตัวไม่ได้มี index เหมือนกัน

migration ที่ลบ `name_slug` เขียนหา index ที่ต้อง drop ด้วย `SHOW INDEX FROM academies`
แล้ว **ข้ามขั้นนี้ไปเมื่อ driver ไม่ใช่ mysql** ⇒ ผ่านบน MySQL แต่ **เทสต์ล้มทั้งไฟล์ 7/7 เคส** บน sqlite:

```
SQLSTATE[HY000]: General error: 1 error in index academies_name_slug_index
after drop column: no such column: "name_slug"
```

**ต้นเหตุ:** migration `2026_07_10_000001` ประกาศ `->index()` ไว้ในสาขา `if (! hasColumn(...))`
- **ฐาน dev (MySQL)** คอลัมน์มีอยู่ก่อนแล้ว ⇒ สาขานั้นไม่วิ่ง ⇒ **ไม่มี index**
- **ฐานเทสต์ (sqlite in-memory)** สร้างใหม่ทุกครั้ง ⇒ สาขาวิ่ง ⇒ **มี index**

⇒ ฐานสองตัวมี schema ไม่ตรงกันจริง ๆ และ MySQL ยอมให้ drop คอลัมน์ที่ยังมี index ค้าง
ส่วน sqlite ไม่ยอม · แก้ด้วย `Schema::getIndexes('academies')` ซึ่งใช้ได้ทุก driver
แล้วกรอง index ที่มี `name_slug` อยู่ในคอลัมน์

**บทเรียน:** ห้ามเขียน migration ที่แตกสาขาตาม `DB::getDriverName()` เพื่อ "ข้าม" งาน schema
— ให้ใช้ API ของ Laravel ที่ครอบทุก driver แทน · และ **ต้องรันเทสต์จริงหลัง migrate ทุกครั้ง**
`php artisan migrate` ผ่านบน MySQL ไม่ได้แปลว่า migration ถูก

---

### 5.10 SET-S8 — แผนแตก shard (3 shard)

| shard | สาระ | ไฟล์ | ขึ้นกับ |
|---|---|---|---|
| **S8-A** | migration drop `name_slug` + ถอดโค้ดสร้าง slug + ย้ายด่านกันชนไปที่ `name` | migration ใหม่ 1 ไฟล์, `AcademyController.php`, `Academy.php`, `AcademyResource.php` | — |
| **S8-B** | frontend: redirect หลังเปลี่ยนชื่อใช้ `name` | `admin/settings.vue` | S8-A |
| **S8-C** | เทสต์: แก้ `AcademySettingsUpdateTest` 4 จุดที่อ้าง slug + เคสชื่อซ้ำ 422 | `tests/Feature/Academy/` | S8-A..B |

**ลำดับ:** A → B → C

#### S8-A รายละเอียด
- migration `drop_name_slug_from_academies_table` — `up()` drop คอลัมน์ (guard `hasColumn`
  · ตรวจ index ก่อน drop เผื่อฐาน production มี index ที่ dev ไม่มี)
  · `down()` เติมคอลัมน์กลับเป็น `string nullable after name_en` — **คืนค่าเดิมไม่ได้** เขียนบอกในหัวไฟล์
  · migration เก่า `2026_07_10_000001` มี `down()` ที่ guard ด้วย `hasColumn` อยู่แล้ว ⇒ rollback ซ้อนไม่พัง
- `updateSettings()` — ลบบล็อกสร้าง slug 9 บรรทัด · เปลี่ยนกฎเป็น
  `'name' => ['required','string','max:255', Rule::unique('academies','name')->ignore($academy->id)]`
- `store()` — เพิ่ม `unique:academies,name` ในกฎของ `name`
- `Academy::$fillable` ถอด `'name_slug'` · `AcademyResource` ถอดคีย์ `'name_slug'`

#### S8-C — 4 จุดในเทสต์เดิมที่จะพังถ้าไม่แก้
1. `setUp()` ส่ง `'name_slug' => 'original-academy-name'` ให้ factory
2. บรรทัด assert `assertEquals('updated-academy-name', $this->academy->name_slug)`
3. `assertJsonStructure` ที่ list `'name_slug'` ⇒ เปลี่ยนเป็น `assertJsonMissingPath('academy.name_slug')`
4. `test_name_slug_regenerates_and_avoids_collision()` ⇒ เขียนใหม่เป็น
   `test_renaming_to_a_taken_name_is_rejected_with_422`

---

### 5.11 การตัดสินใจของเจ้าของโปรเจค รอบ SET-S6 (เคาะแล้ว 2026-08-31)

**D13 — `student_editable_fields` ตั้งค่าด้วยเช็กบ็อกซ์จากแคตตาล็อกปิด ไม่ใช่ช่องแก้ JSON**
19 รายการ = **กลุ่ม 5** (`academic` · `health` · `address` · `contact` · `guardian`)
+ **ฟิลด์อัตลักษณ์ 14** (`citizen_id` · `student_id` · `gender` · `date_of_birth` ·
`title_prefix_th/en` · `first_name_th/en` · `last_name_th/en` · `nickname` · `nationality` ·
`religion` · `profile_image`) · มีตัวเลือกโหมด `blacklist` / `whitelist`
backend ต้อง validate ว่าทุกค่าอยู่ในแคตตาล็อก ⇒ เขียนค่ามั่วเข้าไปไม่ได้

**เหตุผลที่ไม่เปิดช่อง JSON:** ค่านี้คุมว่านักเรียนแก้ข้อมูลตัวเองได้โดยไม่ต้องขออนุมัติหรือไม่
พิมพ์ผิดตัวเดียว (เช่นตกคำว่า `citizen_id`) = นักเรียนแก้เลขบัตรประชาชนกับชื่อตัวเองได้เงียบ ๆ
ซึ่งเป็นรูรั่วที่ migration `2026_08_07_100000` เพิ่งอุดไป

**D14 — `donation_enabled` เป็นสวิตช์เปิด/ปิดชัดเจน เขียนค่า boolean ลงเสมอ**
เลิกปล่อยให้เป็น `NULL` แล้วไป fallback · `AcademyResource` ส่ง**ค่าที่ resolve แล้ว**
จาก `Academy::donationEnabled()` ⇒ UI กับ API อ่านค่าเดียวกันเสมอ

---

### 5.12 🔴 บั๊กที่เจอตอน audit SET-S6

**G21. สวิตช์รับบริจาคของโรงเรียนเป็นสวิตช์หลอกอยู่แล้ววันนี้ — และพร้อมจะกลายเป็นบั๊ก 403 ซ้ำรอย**

`AcademyResource` **ไม่ส่ง `donation_enabled` ออกมาเลย** แต่ [`[name].vue:2556`](../../ui/pages/academies/[name].vue) เขียนว่า
```js
:donation-enabled="academy.donation_enabled !== false"
```
⇒ `undefined !== false` เป็น **true เสมอ** ⇒ แผงรับบริจาคโชว์ตลอด ไม่ว่าค่าในฐานจะเป็นอะไร

ฝั่ง API กลับบังคับใช้จริงผ่าน `AcademyDonatePolicy` และ `AcademyDonateService:95`:
```php
public function donationEnabled(): bool
{
    return $this->donation_enabled ?? (bool) config('platform.course_donation.enabled');
}
```
สังเกตว่า **โรงเรียนไปหยิบ config ของคอร์สมาเป็นค่า fallback** (`platform.course_donation.enabled`)
⇒ วันไหนมีคนตั้ง `COURSE_DONATION_ENABLED=false` ใน `.env` หน้าเว็บจะยังโชว์ปุ่มบริจาค
แต่ API ตอบปฏิเสธ — **แพตเทิร์นเดียวกับบั๊ก 403 หน้า gradebook ใน `d1b54b29` เป๊ะ ๆ**
(UI ใช้นิยามหนึ่ง endpoint ใช้อีกนิยามหนึ่ง)

⇒ SET-S6 ต้องปิดช่องนี้ด้วยการให้ resource ส่ง **ผลลัพธ์ของ `donationEnabled()`** ออกมาตรง ๆ
ห้ามให้ frontend เดาจากคอลัมน์ดิบอีก

**สถานะจริงของทั้งสามสวิตช์ (วัดจากฐาน dev)**

| สวิตช์ | เก็บที่ | บังคับใช้จริง | ค่าใน dev | ตั้งค่าได้ไหม |
|---|---|---|---|---|
| `card_request_flow_enabled` | `academy_settings` (bool) | **5 จุด** — `StudentCardController:490` · `StudentCardManageController:53` · `AcademyStudentCardManageController:74` · `PublicStudentCardRequestController:173` · `StudentCardSyncService:141` | `true` | ❌ |
| `donation_enabled` | `academies` (bool nullable) | `AcademyDonatePolicy` + `AcademyDonateService:95` | **NULL** | ❌ |
| `student_editable_fields` | `academies` (json) | `HandlesStudentUpdates::needsApproval()` — 13 call site ใน 6 controller | blacklist 12 ฟิลด์ | ❌ ตั้งได้เฉพาะผ่าน migration |

**กติกาการจับคู่ของ `needsApproval()`** (ต้องเข้าใจก่อนทำ UI): ชื่อกลุ่มเปล่า ๆ ครอบลูกทั้งหมด
(`academic` ครอบ `academic.gpa`, `academic.create`) เพราะเทียบด้วย `str_starts_with($field, $pattern.'.')`
⇒ **ไม่ต้องมี wildcard ใน UI** — เช็กที่ชื่อกลุ่มพอ

---

### 5.13 SET-S6 — แผนแตก shard (3 shard)

| shard | สาระ | ไฟล์ | ขึ้นกับ |
|---|---|---|---|
| **S6-A** | แคตตาล็อกฟิลด์ + validate/เขียนค่า 3 ตัว + resource ส่งค่าที่ resolve แล้ว | `Academy.php`, `AcademyController.php`, `AcademyResource.php` | — |
| **S6-B** | frontend: แท็บ "ระบบและนโยบาย" + ซ่อมด่านบริจาคบนหน้าโรงเรียน | `admin/settings.vue`, `[name].vue` | S6-A |
| **S6-C** | เทสต์: 3 สวิตช์ round-trip + validate นอกแคตตาล็อกต้อง 422 | `tests/Feature/Academy/` | S6-A..B |

**ลำดับ:** A → B → C

#### S6-A รายละเอียด
- `Academy::STUDENT_EDITABLE_FIELD_CATALOG` — const array 19 คีย์ · **นิยามเดียวของทั้งระบบ**
  controller ใช้ validate · frontend รับผ่าน payload ไม่ hardcode ซ้ำ
- `updateSettings()` เพิ่มกฎ:
  - `card_request_flow_enabled` → `nullable|boolean` เขียนลง `academy_settings` ด้วย `$request->boolean(...)`
  - `donation_enabled` → `nullable|boolean` เขียนลง `academies`
  - `student_editable_fields.mode` → `in:blacklist,whitelist`
  - `student_editable_fields.fields` → `array` · `student_editable_fields.fields.*` → `in:<แคตตาล็อก>`
  - ⚠️ `student_editable_fields` **ไม่อยู่ใน `$fillable`** ⇒ ต้องเซ็ตตรง ๆ ไม่ใช่ `fill()`
  - ⚠️ ถ้าผู้ดูแลติ๊กออกหมด จะไม่มีคีย์ `fields` ส่งมาเลย ⇒ ต้อง normalize เป็น `[]` ไม่ใช่ปล่อยหาย
- `AcademyResource` เพิ่มในบล็อกที่คนดูเนื้อหาได้เห็น:
  `donation_enabled` = **`$this->resource->donationEnabled()`** (ค่า resolve แล้ว ไม่ใช่คอลัมน์ดิบ)
  · `student_editable_fields` · `student_editable_field_catalog` (ให้ UI สร้างเช็กบ็อกซ์ตามนี้)
  · `card_request_flow_enabled` มาทาง `'setting' => $settings` อยู่แล้ว ไม่ต้องเพิ่ม

#### S6-B รายละเอียด
- แท็บใหม่ `system` ชื่อ **"ระบบและนโยบาย"** แทรกระหว่าง "การลงทะเบียน" กับ "โซนอันตราย"
- ทุก input ต้องมี `:disabled="isReadOnly"` ตามแพตเทิร์นของ SET-S4
- FormData: `student_editable_fields` เป็นอาเรย์ ⇒ `String(value)` เดิมใช้ไม่ได้
  ต้อง append เป็น `student_editable_fields[mode]` และ `student_editable_fields[fields][]` ทีละตัว
- **ต้องมีคำเตือนบนจอ** เมื่อ blacklist ว่าง (= นักเรียนแก้ได้ทุกอย่างโดยไม่ต้องขออนุมัติ)
- `[name].vue` — ตอนนี้ `academy.donation_enabled !== false` ใช้ได้แล้วเพราะ resource ส่งค่าจริง
  แต่ให้เขียนเป็น `=== true` เพื่อไม่ให้ค่า `undefined` (payload ของคนนอก) กลายเป็นเปิด

---

### 5.14 🔴 กับดักที่เจอตอน implement SET-S6 — multipart กับกฎ `boolean` ของ Laravel

หลัง shard A/B ลงครบ **เทสต์ผ่านหมด 162/162** แต่พอกดปุ่ม "บันทึก" บนฟอร์มจริงในเบราว์เซอร์
ได้ **422** ทันที:
```
The card request flow enabled field must be true or false. (and 1 more error)
```

**ต้นเหตุ:** หน้าตั้งค่าส่งเป็น `multipart/form-data` ซึ่งส่งได้แต่สตริง และโค้ดเดิมเขียน
`formData.append(key, String(value))` ⇒ boolean กลายเป็น `"true"` / `"false"`
· กฎ `boolean` ของ Laravel รับ `true, false, 1, 0, "1", "0"` — **ไม่รับ `"true"` / `"false"`**

**ทำไมสวิตช์เดิมถึงไม่เคยพัง:** `show_member_list` / `show_course_list` / `privacy`
**ไม่มีกฎ `boolean` คุมอยู่เลย** — controller อ่านผ่าน `$request->boolean()` ตรง ๆ ซึ่งรับ `"true"` ได้
สองสวิตช์ของ SET-S6 เป็น **boolean ตัวแรกของ endpoint นี้ที่ถูก validate** จึงเป็นตัวแรกที่ชนกฎ

**ทำไมเทสต์ทั้งชุดจับไม่ได้:** เทสต์ใช้ `postJson()` ซึ่งส่ง boolean จริงลงไปใน JSON
⇒ ไม่มีทางเจอเส้นทาง multipart เลย · เจอได้ทางเดียวคือกดผ่านฟอร์มจริง
(หมายเหตุ: `AcademyDonationEndpointsTest` เคยเขียนคอมเมนต์เตือนกับดักนี้ไว้แล้วว่า
*"Multipart form data serializes booleans as the strings true/false"* — แต่คนละไฟล์ คนละรอบงาน)

**แก้ที่ frontend** (ไม่ใช่ผ่อนกฎฝั่ง backend):
```js
formData.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value))
```
แล้วเพิ่มเคส `test_multipart_form_accepts_string_boolean_flags` ที่ยิงด้วย `->post()` (ไม่ใช่ `postJson`)
เพื่อล็อกสัญญานี้ไว้

**บทเรียน:** endpoint ที่ frontend เรียกด้วย `FormData` ห้ามเชื่อเทสต์ที่ใช้ `postJson()` อย่างเดียว
— ต้องมีอย่างน้อยหนึ่งเคสที่ยิงเป็น multipart จริง หรือกดผ่านหน้าเว็บจริงก่อนปิดงาน

---

### 5.15 การตัดสินใจของเจ้าของโปรเจค รอบ SET-S7 (เคาะแล้ว 2026-09-01)

**D15 — `director` = "เลือกจากสมาชิกโรงเรียน" (user id) ไม่ใช่ช่องพิมพ์ชื่อ**
คงนิยามที่ `store():99` (`director = auth()->id()`) และ `AcademyResource:73` ใช้อยู่แล้ว
⇒ ฟอร์มเป็น **ช่องค้นหาสมาชิก** (ไม่ใช่ dropdown — โรงเรียนเดียวมีสมาชิก **3,063 แถว / APPROVED 2,614**
ส่วนใหญ่เป็นนักเรียน) · backend validate `integer` + `exists` + **ต้องเป็นสมาชิกของโรงเรียนนี้ที่ status=2**
· และต้องปิด G22 (ล่าง) ไปพร้อมกัน ไม่งั้นเปิดให้ตั้งค่าโดยที่คอลัมน์ยังรับสตริงอะไรก็ได้จากประตูหลัง

**D16 — `established_year` เก็บเป็น พ.ศ.** (`min:2400` ถึง `ปีปัจจุบัน + 543` คำนวณจาก `now()->year`)
หน้าโรงเรียนแสดง **"ก่อตั้ง พ.ศ. 2510 (1967)"** — ค.ศ. ในวงเล็บคือ `พ.ศ. − 543`
⇒ ต้องแก้ `UpdateAcademyRequest` ที่บังคับ `min:1800|max:2200` (= ค.ศ.) ให้ตรงกัน
ไม่งั้นสองประตูเขียนคนละหน่วยลงคอลัมน์เดียวกัน (แพตเทิร์นเดียวกับ G21/G22 — สองนิยามในคอลัมน์เดียว)
**ไม่มีข้อมูลเก่าต้องแปลง** — ทั้งตารางเป็น NULL

**D17 — `social_media_links` เป็น JSON แคตตาล็อกปิด 6 ช่อง + โชว์บนหน้าโรงเรียน**
`facebook` · `line` · `youtube` · `tiktok` · `instagram` · `x` — คีย์นอกแคตตาล็อกต้อง 422
เหตุผลที่ต้องโชว์ด้วย: ถ้าตั้งค่าได้แต่ไม่มีที่แสดง มันคือ **สวิตช์หลอกอีกตัว** แบบเดียวกับที่ S5/S6 เพิ่งไล่ปิด

**D18 — `approval_flow` ลบทิ้ง** (migration drop + `down()` คืนคอลัมน์ได้จริง)
ไม่มีผู้อ่านทั้ง `app/` และ `ui/` (grep ยืนยัน 0) เกิดมาจาก migration `2026_06_18_013944` แล้วไม่เคยถูกต่อ
ตัดหนี้แบบเดียวกับ S5 (drop `allow_*_registration`) และ S8 (drop `name_slug`)
วันหน้าถ้าต้องการอนุมัติ 2 ชั้นจริง ค่อยออกแบบใหม่พร้อมคนที่อ่านค่า

---

### 5.16 🔴 บั๊กที่เจอตอน audit SET-S7 (G22 มีการแก้ข้อมูลย้อนหลัง — อ่านกรอบเตือนด้านล่าง)

**G22. ฟิลด์ `director` มีสองนิยามในคอลัมน์เดียว — และประตูหลังเขียนอะไรลงไปก็ได้**

`AcademyResource:73` เขียนว่า `new UserResource(User::find($this->director))` ⇒ ตีความ `director` เป็น **user id**
(`store():99` ก็เซ็ต `director = auth()->id()`) และหน้าโรงเรียนอ่านเป็นอ็อบเจกต์ (`director.name`, `director.avatar`)
แต่คอลัมน์จริงเป็น **varchar(255)** และมีอีกประตูหนึ่งที่ยิงสตริงอะไรก็ได้เข้ามา:
`PUT /api/admin/academies/{id}` ([routes/admin/admin.php:379](../../api/nuxnanravel/routes/admin/admin.php)) ใช้
`UpdateAcademyRequest` ที่ validate ว่า `'director' => nullable|string|max:255`
⇒ **แอดมินแพลตฟอร์มพิมพ์ "นายสมชาย" ลงไปได้วันนี้เลย** แล้วการ์ดผู้อำนวยการจะหายไปเงียบ ๆ
(และ `User::find('นายสมชาย')` คือ query สตริงเทียบ primary key ที่เป็น int — เปลืองเปล่าและไม่มีวันเจอ)

> ⚠️ **แก้ข้อมูลของตัวเอง (2026-09-01):** ตอน audit ผมสรุปว่าเคสนี้ทำให้ `GET /api/academies/{name}`
> ตอบ **500** โดยอ้าง TypeError จาก `method_exists(null, ...)` ใน `UserResource:113`
> — **ผิด** · TypeError นั้นเกิดตอนผมเรียก `(new UserResource(null))->toArray()` **ตรง ๆ** ใน tinker เท่านั้น
> เมื่อ resource ตัวนี้ถูกซ้อนอยู่ใน `AcademyResource` แล้วให้ framework serialize จริง
> Laravel แปลง resource ที่ห่อ null ให้เป็น `null` ให้เอง — **ทดสอบซ้ำบน MySQL ด้วยโค้ดเวอร์ชันก่อนแก้**
> ทั้งค่า `'999999'`, `'ผอ.สมชาย'` และ `''` ⇒ ได้ HTTP ปกติ คีย์ `director` = `null` ทั้งสามเคส ไม่มี exception
> ยืนยันซ้ำด้วย **mutation check**: ถอด null-guard ออกแล้ว **ไม่มีเทสต์ไหนล้มเลย**
> ⇒ null-guard ใน resource เป็นการ**กันไว้** (และตัด query ที่ไม่มีประโยชน์ทิ้ง) ไม่ใช่การปิดช่อง 500
> สิ่งที่เป็นบั๊กจริงคือ **สองประตูเขียนคนละชนิดลงคอลัมน์เดียว** ซึ่ง S7 ปิดที่ประตูทั้งสองบานแล้ว

**สิ่งที่ S7 ทำ:** validate ที่ประตูหน้า (ต้องเป็นสมาชิก APPROVED หรือเจ้าของ) · ที่ประตูหลังเปลี่ยนเป็น
`integer|exists:users,id` · และ resource ใช้ `is_numeric` ก่อน `User::find` แล้วคืน `null` เมื่อหาไม่เจอ

**G23. `social_media_links` กฎ `max:500` แต่คอลัมน์กว้าง 255**
`UpdateAcademyRequest:41` ยอมให้ยาว 500 · MySQL strict mode จะโยน `1406 Data too long` ⇒ 500 ไม่ใช่ 422
D17 แก้ทั้งคู่อยู่แล้ว (คอลัมน์เป็น json + validate ทีละคีย์)

**G24. `type` ไม่มีแคตตาล็อกที่ backend เลย และ FE hardcode ซ้ำ 3 ที่**
`ui/pages/academies/index.vue:147` · `ui/pages/academies/[name].vue:1115` · `ui/components/widgets/AllAcademiesWidget.vue:43`
เก็บ map เดียวกันคนละก๊อบปี้ (`public`/`private`/`foundation`/`international`) และ `index.vue:59` ใช้เป็น
**ตัวกรองรายการโรงเรียน** ⇒ ถ้าเปิดให้พิมพ์อิสระ โรงเรียนจะหลุดจากตัวกรองเงียบ ๆ
⇒ ทำแบบเดียวกับ D13: `Academy::ACADEMY_TYPE_CATALOG` เป็นนิยามเดียว · controller `Rule::in` ·
resource ส่งแคตตาล็อกให้ UI สร้างตัวเลือก (ห้าม hardcode ซ้ำในหน้าตั้งค่า)

**G25 (เล็ก). การ์ด About ใน `[name].vue` มีสองชุด** (ราว ๆ บรรทัด 2581–2640 และ 2665–2725 — มือถือ/เดสก์ท็อป)
อะไรที่เพิ่มต้องเพิ่ม **ทั้งสองที่** ไม่งั้นจอหนึ่งเห็นอีกจอไม่เห็น · และตอนนี้ทั้งสองชุด
**ไม่แสดง `website` เลย** ทั้งที่แก้ได้จากแท็บข้อมูลติดต่อ

---

### 5.17 SET-S7 — แผนแตก shard (5 shard)

| shard | สาระ | ไฟล์ | ขึ้นกับ |
|---|---|---|---|
| **S7-A** | migration 2 ตัว: `social_media_links` varchar→json · drop `approval_flow` (D17 + D18) | `database/migrations/` × 2 | — |
| **S7-B** | backend: แคตตาล็อก 2 ชุด + validate/เขียน 5 ฟิลด์ + **ปิด G22** + ซ่อม `UpdateAcademyRequest` | `Academy.php`, `AcademyController.php`, `AcademyResource.php`, `Admin/UpdateAcademyRequest.php` | S7-A |
| **S7-C** | frontend: 5 ฟิลด์เข้าแท็บ "ข้อมูลทั่วไป" (ช่องค้นหาผอ. + select ประเภท + ปี พ.ศ. + 6 ช่องโซเชียล) | `admin/settings.vue` | S7-B |
| **S7-D** | frontend: หน้าโรงเรียนแสดงปีก่อตั้งเป็นไทย + แถวไอคอนโซเชียล **ทั้ง 2 ชุดการ์ด** | `[name].vue` | S7-B |
| **S7-E** | เทสต์: round-trip 5 ฟิลด์ · ค่านอกแคตตาล็อก 422 · ผอ.ที่ไม่ใช่สมาชิก 422 · **director ชี้ user ที่ไม่มี ⇒ resource ต้องไม่ 500** · multipart จริง | `tests/Feature/Academy/` | S7-A..D |

**ลำดับ:** A → B → C → D → E

#### S7-A รายละเอียด
- `social_media_links`: `$table->json('social_media_links')->nullable()->change()` · `down()` คืนเป็น
  `string(255)->nullable()->change()` · **ทั้งตารางเป็น NULL อยู่แล้ว** จึงไม่มีข้อมูลเก่าให้แปลง (ยืนยันด้วย query)
- `approval_flow`: `dropColumn` · `down()` คืน `string('approval_flow', 191)->default('single')`
- ⚠️ migration เก่า `2026_06_18_013944` มี `dropColumn(['student_editable_fields','approval_flow'])` ใน `down()`
  ⇒ ต้อง rollback ของเราก่อนเสมอ (ลำดับปกติของ `migrate:rollback` ถูกอยู่แล้ว) แต่อย่าไปแก้ไฟล์เก่า
- ⚠️ **เทสต์ผ่านบน SQLite ไม่ได้แปลว่า migration รันบน MySQL ได้** — ต้อง `php artisan migrate` บน dev จริง
  แล้ว `SHOW COLUMNS` ยืนยันชนิดคอลัมน์ด้วยตา

#### S7-B รายละเอียด
- `Academy::ACADEMY_TYPE_CATALOG = ['public','private','foundation','international']` (null = "ทั่วไป")
- `Academy::SOCIAL_LINK_CATALOG = ['facebook','line','youtube','tiktok','instagram','x']`
- `$casts` เพิ่ม `'social_media_links' => 'array'`
- `updateSettings()` เพิ่มกฎ:
  - `slogan` → `nullable|string|max:255`
  - `type` → `nullable|string` + `Rule::in(ACADEMY_TYPE_CATALOG)`
  - `established_year` → `nullable|integer|min:2400|max:` + `(now()->year + 543)` **(พ.ศ. ตาม D16)**
  - `director` → `nullable|integer` + rule ที่บังคับว่า **เป็นสมาชิก APPROVED (status=2) ของโรงเรียนนี้**
    (`Rule::exists('academy_members','user_id')->where('academy_id',$academy->id)->where('status',2)`)
    — เจ้าของโรงเรียนอาจไม่มีแถวสมาชิก (ดู memory "Academy membership facts") ⇒ ต้องยอมรับ `$academy->user_id` ด้วย
  - `social_media_links` → `nullable|array` · `social_media_links.<คีย์>` → `nullable|url|max:255` ต่อคีย์
    · **ห้ามใช้ `.*` เฉย ๆ** ต้องล็อกคีย์ตามแคตตาล็อก ไม่งั้นคีย์มั่วเข้าฐานได้
  - ทั้ง 5 ฟิลด์อยู่ใน `$fillable` แล้ว ยกเว้นต้องเช็กว่า `fill()` ครอบคีย์ใหม่ครบ
  - normalize: ตัดคีย์ที่ว่างออกก่อนเก็บ ⇒ เก็บ `{}` ไม่ใช่ `{"facebook":null,...}`
- `AcademyResource`:
  - **ปิด G22:** `$director = $this->director ? User::find($this->director) : null;`
    `'director' => $director ? new UserResource($director) : null`
  - เพิ่ม `'academy_type_catalog' => Academy::ACADEMY_TYPE_CATALOG`
    และ `'social_link_catalog' => Academy::SOCIAL_LINK_CATALOG`
- `Admin/UpdateAcademyRequest`: `director` → `nullable|integer|exists:users,id` ·
  `established_year` → `min:2400|max:<ปีปัจจุบัน+543>` · `social_media_links` → `nullable|array` + คีย์ตามแคตตาล็อก
  · `type` → `Rule::in(ACADEMY_TYPE_CATALOG)` (ปิด G23 + G24 ที่ประตูหลัง)

#### S7-C รายละเอียด
- ฟิลด์ใหม่ทั้งหมดอยู่ในแท็บ **"ข้อมูลทั่วไป"** (ไม่เปิดแท็บใหม่)
- ประเภทโรงเรียน: `<select>` สร้างจาก `academy.academy_type_catalog` + map ป้ายไทยในหน้า
  (แพตเทิร์นเดียวกับ `FIELD_LABELS` ของ S6) + ตัวเลือก "ไม่ระบุ" = `null`
- ผอ.: **ช่องค้นหา** ยิง `GET /api/academies/{id}/members/search?search=&status=2&per_page=10` (debounce ≥300ms
  · เริ่มค้นเมื่อพิมพ์ ≥ 2 ตัว) แสดงผลลัพธ์เป็นรายการกดเลือก · ผอ.ปัจจุบันแสดงเป็นชิปพร้อมปุ่มเอาออก
  (ค่าเริ่มต้นอ่านจาก `academy.director` ที่เป็นอ็อบเจกต์อยู่แล้ว)
- ปีก่อตั้ง: `type="number"` + placeholder `เช่น 2510` + ข้อความช่วย "ระบุเป็น พ.ศ."
- โซเชียล 6 ช่อง: `type="url"` ต่อช่อง สร้างจาก `academy.social_link_catalog`
- ⚠️ **multipart:** `Object.entries(form.value)` เดิมยิง `String(value)` ⇒ อ็อบเจกต์จะกลายเป็น `[object Object]`
  ต้อง `return` ข้าม `social_media_links` แล้ว append เป็น `social_media_links[facebook]` ทีละคีย์
  (แพตเทิร์นเดียวกับ `student_editable_fields` ใน S6) · `director` ว่าง = ส่งสตริงว่าง ซึ่ง
  `ConvertEmptyStringsToNull` แปลงเป็น `null` ให้แล้ว (กฎ `nullable` จึงผ่าน) — แต่ให้ยืนยันด้วยการยิงจริง
- ทุก input ต้องมี `:disabled="isReadOnly"` ตามแพตเทิร์นของ S4

#### S7-D รายละเอียด
- ปีก่อตั้ง: เปลี่ยน `Established {{ academy.established_year }}` เป็น
  `ก่อตั้ง พ.ศ. {{ y }} ({{ y - 543 }})` — **แก้ทั้ง 2 ชุดการ์ด** (G25)
- แถวไอคอนโซเชียล: วนจากคีย์ที่มีค่าเท่านั้น · `target="_blank" rel="noopener noreferrer"` ·
  touch target ≥ 44px · ซ่อนทั้งบล็อกเมื่อไม่มีลิงก์เลย
- mobile-first: แถวไอคอนต้อง `flex-wrap` + `flex-shrink-0` และตรวจที่ 375px

#### S7-E รายละเอียด
- round-trip 5 ฟิลด์ผ่าน `postJson` (owner)
- `type` นอกแคตตาล็อก ⇒ 422 · `established_year` = 1967 (ค.ศ.) ⇒ 422 · ปีอนาคต ⇒ 422
- `director` = user ที่ไม่ได้เป็นสมาชิก ⇒ 422 · `director` = สมาชิก status≠2 ⇒ 422
- **เคส G22:** ตั้ง `director` เป็น id ที่ไม่มีในตาราง `users` และเป็นสตริงไทย ผ่าน DB ตรง ๆ
  แล้วยิง `GET /api/academies/{name}` ⇒ ต้องได้ **200 พร้อม `director: null`**
  (เป็นเทสต์ characterization — mutation check พิสูจน์แล้วว่าโค้ดเวอร์ชันก่อนแก้ก็ผ่านเคสนี้)
- `social_media_links` คีย์นอกแคตตาล็อก ⇒ 422 · ค่าไม่ใช่ URL ⇒ 422 · ส่งช่องว่างทั้งหมด ⇒ เก็บเป็นอาเรย์ว่าง `[]`
  (cast `array` ของ Laravel serialize อาเรย์ว่างเป็น `[]` ไม่ใช่ `{}` — อ่านกลับมาได้ `[]` เหมือนกัน)
- **1 เคสยิง multipart จริงด้วย `->post()`** (บทเรียนจาก §5.14) ครอบทั้ง `social_media_links[...]` และ `director`

---

### 5.18 🔴 กับดักที่เจอตอน implement SET-S7 — "รูปโปรไฟล์" มีสองชื่อคีย์ในระบบเดียวกัน

สเปคของ shard C สั่งให้ช่องค้นหาผอ. อ่านรูปจาก `m.user.avatar` เพราะการ์ดผอ.บนหน้าโรงเรียน
ใช้ `academy.director.avatar` อยู่แล้ว — **แต่คนละ resource คนละชื่อคีย์**:

| แหล่ง | resource | คีย์รูป |
|---|---|---|
| `academy.director` | `UserResource` | `avatar` **และ** `profile_photo_url` (ส่งทั้งคู่) |
| ผลลัพธ์จาก `/members/search` | `AcademyMemberResource` → บล็อก `'user' =>` | **`profile_photo_url` เท่านั้น** (ไม่มีคีย์ `avatar`) |

⇒ ถ้าปล่อยตามสเปค รายการผลค้นหาและชิปผอ.จะขึ้นไอคอนสำรองตลอด ไม่มีวันแสดงรูปจริง
**Claude แก้เอง** ที่ `settings.vue` ให้อ่าน `profile_photo_url` ก่อน แล้วค่อย fallback ไป `avatar`
(ยืนยันคีย์ด้วยการยิง `/api/academies/1/members/search` จริงแล้วพิมพ์ `array_keys($first['user'])` ออกมาดู)

**บทเรียน:** ก่อนเขียนสเปคที่อ้างคีย์ของ payload — ต้องยิง endpoint นั้นดูของจริงก่อน
อย่าเดาจากหน้าอื่นที่ใช้คนละ resource แม้จะเป็น "ผู้ใช้" เหมือนกัน

**ของแถมที่เจอระหว่างตรวจ (ไม่ใช่ของ S7):** `GET /api/notifications/recent?limit=10` ตอบ **500 ทุกครั้ง**
ที่โหลดหน้าไหนก็ตาม — `SQLSTATE[42S22] Unknown column 'avatar' in 'field list'`
(query เลือก `select id, name, avatar from users` ทั้งที่ตาราง `users` ไม่มีคอลัมน์ `avatar` —
รูปมาจาก accessor `profile_photo_url`) · **ต้นตอเดียวกับกับดักข้างบนเป๊ะ ๆ** · เปิดเป็นงานแยกไว้แล้ว

---

## 6. Implementation Tasks

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| **SET-S1** | อุดช่องโหว่สิทธิ์ (G1 + G5) | — | ลบ route+method `PATCH /{academy}/update` · ย้าย `POST /{academy}/settings` ไปใช้ `academy.permission:settings.manage` แล้วถอดด่านที่เขียนเองใน controller | 🟢 **verified** (ยังไม่ commit) |
| **SET-S2** | เก็บถาวรโรงเรียน แทนการลบ (G2 · D1 + D8–D11) | SET-S1 | migration `archived_at` + `POST/DELETE /{academy}/archive` + `GET /academies/archived` (เจ้าของ + super admin ตาม D9) + กรอง 5 จุด listing + แท็บโซนอันตรายเรียกของจริง + ส่วน "เก็บถาวรแล้ว" บน `/academies` | 🟢 **verified + migrate แล้ว** |
| **SET-S3** | รวมคีย์สิทธิ์ให้เหลือชุดเดียว (§4) | SET-S1 | ถอด `academy.settings.view/edit` ออกจากแคตตาล็อก+SYSTEM_ROLES · ย้าย 6 จุดใน FE มาใช้ `settings.*` · migration ย้ายคีย์ในแถว `academy_roles` จริง | 🟢 **verified + migrate แล้ว** |
| **SET-S4** | โหมดดูอย่างเดียว (G6) | SET-S3 | `settings.view` เข้าหน้าได้แบบ read-only · ปุ่มบันทึก+ปุ่มอัปโหลดซ่อน · 16 ช่อง disabled · แถบแจ้งเตือน · แท็บโซนอันตรายเฉพาะเจ้าของ · API ยังกัน `settings.manage` | 🟢 **verified** |
| **SET-S5** | ทำให้สวิตช์มีผลจริง (G3 + G4 · D2 + D3) | SET-S1 | บังคับใช้ `privacy`/`show_member_list`/`show_course_list` · drop `allow_*_registration` 2 คอลัมน์ + ถอดออกจากฟอร์ม · `join_mode` เป็นตัวหลักแทน `auto_accept_members` และ `invite_only` บล็อกการขอเข้าร่วมจริง | 🟢 **verified + migrate แล้ว** |
| **SET-S6** | เพิ่มแท็บ "ระบบและนโยบาย" (G9 ข้อ 1–3 · G21 · D13 + D14) | SET-S1 | `card_request_flow_enabled`, `student_editable_fields` (เช็กบ็อกซ์จากแคตตาล็อกปิด 19 รายการ), `donation_enabled` ตั้งค่าได้จากหน้าจอ + resource ส่งค่าบริจาคที่ resolve แล้ว (ปิด G21) | 🟢 **verified** |
| **SET-S7** | เพิ่มฟิลด์อัตลักษณ์โรงเรียน (G9 ข้อ 4–5 · G22–G25 · D15–D18) | SET-S6 | `slogan`, `established_year` (พ.ศ.), `type` (แคตตาล็อกปิด 4), `director` (เลือกจากสมาชิก), `social_media_links` (JSON 6 ช่อง) เข้าแท็บ "ข้อมูลทั่วไป" + โชว์บนหน้าโรงเรียน · **ปิด G22 (director เขียนได้สองชนิดจากสองประตู)** · drop `approval_flow` | 🟢 **verified + migrate แล้ว** (เทสต์ใหม่ 11 เคส · mutation check 5 แบบ) |
| **SET-S8** | ลบ `name_slug` ทิ้ง + ซ่อม redirect (G7 · D12) | SET-S1 | migration drop คอลัมน์ `name_slug` · ถอด `Str::slug` ออกจาก `updateSettings` · ย้ายด่านกันชื่อซ้ำไปไว้ที่คอลัมน์ `name` ที่เป็น UNIQUE จริง (ปิด G19 — เดิมได้ 500 พร้อม SQL ดิบ) · redirect หลังเปลี่ยนชื่อใช้ `name` + encodeURIComponent | 🟢 **verified + migrate แล้ว** |
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
