# 24 — แดชบอร์ด (Admin Dashboard)

> ไฟล์รองของเมนู `#24 แดชบอร์ด` ใน [OVERVIEW.md](OVERVIEW.md)
> อ่านคู่กับ OVERVIEW.md · **เมนูนี้ทำท้ายสุดโดยตั้งใจ** เพราะหน้าที่คือ "รวม signal" จากเมนู #1–#23 ที่ปิดไปแล้ว
> audit โค้ดจริง 2026-10-15 (ขั้น [1]+[2]) — ยังไม่ส่ง step ให้ agy/codex

## 1. Scope & Purpose

หน้า landing แรกที่ admin/เจ้าของเห็นเมื่อเข้า `/academies/{name}/admin` — ต้องตอบ 3 คำถามใน 1 จอ:
1. **ตอนนี้โรงเรียนมีอะไรบ้าง** (จำนวนนักเรียน/ครู/วิชา/ห้อง) — signal เชิงปริมาณ
2. **มีอะไรรอฉันทำ** (คำขอเข้าร่วม, รายการรอตรวจสอบ ฯลฯ) — signal เชิง action
3. **ทางลัดไปงานที่ทำบ่อย** (quick actions)

**ผู้ใช้:** owner, admin, department admin, และ role ย่อยที่ถือ `academy.view` — การ์ด/ทางลัดแต่ละอันต้องโผล่ตามสิทธิ์จริงของผู้ใช้ ไม่ใช่โชว์หมดแล้วเด้ง 403 ตอนกด

**ไม่อยู่ใน scope:** การแก้ไขข้อมูลเชิงลึก (อยู่ในเมนูปลายทาง) · dashboard widget builder แบบลากวาง (`DashboardWidgetController` มีอยู่แต่เป็นระบบแยก — ดู §5 G12)

## 2. Current State (จากการสแกนโค้ดจริง)

### Frontend
- Page: [`ui/pages/academies/[name]/admin/index.vue`](../../ui/pages/academies/[name]/admin/index.vue) — หน้าเดียว, `layout: 'main'`, `ssr: false`
  - **ไม่มีหน้าซ้ำ** — `admin/dashboard/admin.vue` และ `admin.vue` (parent) ไม่มีจริง (ปิดข้อกังวล OVERVIEW §140)
  - โหลดข้อมูลใน `onMounted`: resolve academy by name → ยิง 3 กลุ่มขนาน (`fetchStats` / `fetchPendingRequests` / `fetchRecentActivities`)
- Component: [`AcademyAdminRevenueWidget.vue`](../../ui/components/academy/revenue/AcademyAdminRevenueWidget.vue) — 4 การ์ดรายได้ (แต้มคงเหลือ/รายได้โฆษณา/รอตรวจสอบ/แคมเปญเปิด)
- Composable: `useAcademyRole(academyId)` (→ `can()`), `useAcademyRevenue(academyId)` (→ `fetchRevenueDashboard` ยิง `/revenue/revenue`)

### Backend (endpoint ที่หน้านี้เรียก)
| Endpoint | Controller | Gate (route) | ใช้ทำอะไร |
|---|---|---|---|
| `GET /academies/{name}` | AcademyController | — | resolve id |
| `GET /{academy}/members/stats` | AcademyMemberController@getMemberStats | `academy.permission:members.view` | นักเรียน/ครู/รอดำเนินการ |
| `GET /{academy}/courses` | AcademyCourseController@getAcademyCourses | `academy.visibility:courses` | นับวิชา |
| `GET /{academy}/groups` | AcademyGroupController@index | `academy.visibility:content` | นับกลุ่ม/ห้อง |
| `GET /{academy}/members?role=teacher` | AcademyMemberController@getAcademyMembers | `academy.visibility:members` | fallback นับครู |
| `GET /{academy}/pending-requests` | AcademyMemberController@getPendingRequests | **auth:api เปล่า** | รายการคำขอ |
| `GET /{academy}/activities` | AcademyActivityController@getActivities | `academy.visibility:content` | กิจกรรมล่าสุด |
| `POST /{academy}/members/{member}/accept` | AcademyMemberController@acceptmember | (ในกลุ่ม members) | อนุมัติ |
| `POST /{academy}/members/{member}/reject` | AcademyMemberController@rejectmember | (ในกลุ่ม members) | ปฏิเสธ |
| `GET /{academy}/revenue/revenue` | AcademyRevenueController@revenue | `academy.permission:finance.view` | widget รายได้ |

### Database
ไม่มีตารางของตัวเอง — อ่าน aggregate จาก `academy_members`, `courses`, `academy_groups`, `academy_activities`, และ revenue domain

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | การ์ดสรุป นักเรียน/ครู/วิชา/ห้อง | ⚠️ | มี แต่ "นักเรียน" นับรวมทุก role (G2), วิชา/ห้องใช้ `.length` เปราะ (G4) |
| 2 | การ์ด "รอดำเนินการ" (คำขอเข้าร่วม) | ✅ | ตรงกับ `stats.pending` |
| 3 | การ์ด "ใช้งานวันนี้" | ❌ | ค่าตาย = 0 ตลอด ไม่มี source (G1) |
| 4 | Quick actions ตามสิทธิ์ | ⚠️ | 6/8 gate ด้วย `can()` · 2 อัน `show:true` ค้าง (G6) · ลิงก์รายงานวนกลับตัวเอง (G3) |
| 5 | รายการคำขอ + ปุ่มอนุมัติ/ปฏิเสธ | ⚠️ | ทำงานได้ แต่ optimistic นับ student เพิ่มเสมอ (G5) |
| 6 | กิจกรรมล่าสุด | ⚠️ | แสดงดิบ ไม่ฟอร์แมตวันที่ (G9) |
| 7 | สรุปรายได้ (widget) | ⚠️ | ไม่ gate `finance.view` ที่ FE → section ว่าง+error เงียบสำหรับ role ไม่มีสิทธิ์ (G7) |
| 8 | page guard `academy.view` | ❌ | ไม่มี middleware เลย (G6) |
| 9 | error / empty state ระดับหน้า | ❌ | resolve academy ล้ม = จอว่างเงียบ (G10) |
| 10 | รวม signal จากเมนูที่ปิดแล้ว (เข้าเรียน/ตารางสอน/ผลการเรียน/เยี่ยมบ้าน/เลือกตั้ง) | ❌ | แดชบอร์ดยังไม่ดึง signal เหล่านี้เลย — เป็นโอกาสหลักของ "ทำท้ายสุด" (ต้องเคาะ Q) |

## 4. Permission Matrix

| Permission key | Owner | Admin | ฝ่าย admin | Teacher | Staff | Student | Guardian |
|---|---|---|---|---|---|---|---|
| `academy.view` (เข้าหน้า) | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| การ์ด/ทางลัดแต่ละอัน | ตาม key ของเมนูปลายทาง (`members.view`, `courses.create`, `finance.view`, `settings.manage`, `reports.view`, `announcements.create` …) |

> หลักการ: หน้าเปิดให้ผู้ถือ `academy.view` ทุกคน แต่ **แต่ละการ์ดโผล่ตาม `can()` ของ key ปลายทาง** เพื่อไม่ให้กดแล้วเด้ง 403 (บทเรียนซ้ำ #14 F1 / #19 G1)

## 5. Gap Analysis

**correctness (แดชบอร์ดรายงานเลขผิด — ขัดหน้าที่เมนูโดยตรง):**
- **G1** การ์ด "ใช้งานวันนี้" = 0 ตลอดกาล (`index.vue:176-182`) — ไม่มี fetch เขียนค่า ต้องมี endpoint จริงหรือถอดการ์ดทิ้ง → **ติด Q1**
- **G2** การ์ด "นักเรียน" ใช้ `stats.approved` = สมาชิก approved ทั้งหมด (ครู+นักเรียน+บุคลากร) แต่ติดป้าย "นักเรียน" ทั้งที่ API ส่ง `by_role.student` มาให้แล้ว (`:63`)
- **G3** quick action "ดูรายงาน" ลิงก์ `.../admin` (วนกลับตัวเอง) ควรเป็น `.../admin/reports` (เมนู #21 มีจริง) (`:230`)
- **G4** นับวิชา/กลุ่มด้วย `.length` ของ array (`:74`, `:81`) — เปราะถ้า endpoint เปลี่ยนเป็น paginate; ควรใช้ count/meta.total ให้ชัด
- **G5** optimistic accept เพิ่ม `totalStudents++` เสมอ แม้คนที่อนุมัติเป็นครู (`:257`)

**authz / guard:**
- **G6** หน้าไม่มี page guard — `definePageMeta` มีแค่ layout+ssr (`:9-12`) ไม่มี middleware `academy.view` · quick action "จัดการกลุ่ม" (`:208`) + "ฝ่ายงาน/แผนก" (`:216`) ตั้ง `show:true` ไม่ผ่าน `can()`
- **G7** widget รายได้เรนเดอร์ด้วย `v-if="academyId"` เท่านั้น (`:362`) ไม่ gate `finance.view` ที่ FE — endpoint gate จริงแล้ว (`academy.php:1199`) จึง **ไม่ใช่ data leak** แต่ role ไม่มี finance จะเห็นหัวข้อ "สรุปรายได้" + ช่องว่าง + ยิง 403 ที่ถูกกลืน ควรครอบทั้ง section ด้วย `can('finance.view')` (ตอนนี้ครอบแค่ลิงก์ `:366`)

**polish:**
- **G8** avatar คำขอใช้ `<img :src="profile_photo_url">` ตรง ๆ (`:400`) ไม่ผ่าน `useAvatar` — เสี่ยงกับดัก storage URL
- **G9** `activity.created_at` แสดงดิบ (`:453`) ไม่ฟอร์แมต/relative time
- **G10** ไม่มี error state — resolve academy ไม่ success (`:39`) → `isLoading=false` แล้วจอว่างเงียบ

**ทิศทาง (ไม่ใช่บั๊ก แต่คือเหตุผลที่เมนูนี้ทำท้ายสุด):**
- **G11** แดชบอร์ดยังไม่ดึง signal จากเมนูที่ปิดไปแล้ว (เข้าเรียนวันนี้ #18 / คาบสอนวันนี้ #11 / ผลการเรียนค้างกรอก #19 / เยี่ยมบ้านค้าง #17 / เลือกตั้งที่เปิดอยู่ #25 / รายการการเงินรอตรวจ #23) → **ติด Q2**
- **G12** `DashboardWidgetController` (`academy.php:980-995`, gate `settings.manage`) เป็นระบบ widget แบบตั้งค่าเองที่มีอยู่แต่หน้านี้ไม่ใช้เลย — ต้องตัดสินว่าจะผูกเข้าด้วยกันหรือทิ้ง → **ติด Q3**

**หนี้ข้ามเมนู (ไม่แก้ที่ #24):**
- `GET /{academy}/pending-requests` (`academy.php:156,207`) อยู่ใน `auth:api` เปล่า ไม่มี `academy.permission` → ฝากเมนู #3 (join-requests)

## 6. Open Questions — ✅ เคาะครบแล้ว 2026-10-15

- **Q1 (G1):** ✅ **เคาะ = endpoint ใหม่นับ `last_seen`** — "ใช้งานวันนี้" = จำนวนสมาชิกที่ `last_seen` (หรือ activity ล่าสุด) ตรงวันนี้
  ⚠️ **ผลต่องาน:** ต้องมีกลไกเก็บ `last_seen` ต่อ user ก่อน — ตรวจว่ามีคอลัมน์/middleware อัปเดต `last_seen` อยู่แล้วหรือไม่ ถ้าไม่มีต้องเพิ่ม (migration + middleware แตะทุก authenticated request) แล้วค่อยเขียน endpoint นับ ⇒ DB-S7 หนักกว่าที่คิด แตกเป็น 2 ส่วน
- **Q2 (G11):** ✅ **เคาะ = ชุดเล็ก action-ค้าง ก่อน** — รอบนี้เพิ่ม 3 signal: **การเงินรอตรวจ (#23)** + **เข้าเรียนวันนี้ (#18)** + **ผลการเรียนค้างกรอก (#19)** ผ่าน endpoint รวมตัวเดียว (เลียน `reports/summary` ของ #21) · signal เมนูอื่น (#11 ตารางสอน, #17 เยี่ยมบ้าน, #25 เลือกตั้ง) เลื่อนเป็นเฟส 2
- **Q3 (G12):** ✅ **เคาะ = ปล่อยไว้ ไม่ผูก** — `DashboardWidgetController` คง dormant, หน้านี้ใช้ layout hardcode ต่อ ⇒ DB-S9 เหลือแค่ทำ note ว่า dormant ไม่มีโค้ด

## 7. Implementation Tasks

> แบ่งเป็น 2 คลื่น: **คลื่น A = parity/บั๊ก (ไม่ติด Q, ส่งได้เลย)** · **คลื่น B = signal รวม (ติด Q1/Q2/Q3)**

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| **DB-S1** | แก้นิยามการ์ดให้ตรงความหมาย (G2+G4) | — | ใช้ `by_role.student` เป็น "นักเรียน", `by_role.teacher` เป็น "ครู" (ตัด fallback members?role ทิ้ง), นับวิชา/กลุ่มจาก count/meta ชัด ๆ | ⚪ |
| **DB-S2** | ซ่อมลิงก์ quick action + สิทธิ์ (G3+G6 บางส่วน) | — | ลิงก์รายงาน→`/admin/reports`, "จัดการกลุ่ม"/"ฝ่ายงาน" gate ด้วย `can('groups.view')`/`can('departments.view')` แทน `show:true` | ⚪ |
| **DB-S3** | page guard `academy.view` (G6) | — | เพิ่ม middleware เข้าหน้า (เทียบหน้า admin เมนูอื่นว่า guard ยังไง) + redirect ถ้าไม่ผ่าน | ⚪ |
| **DB-S4** | gate widget รายได้ (G7) | — | ครอบ section "สรุปรายได้" ด้วย `can('finance.view')` ทั้งก้อน (ไม่ยิง endpoint/ไม่โชว์หัวข้อถ้าไม่มีสิทธิ์) | ⚪ |
| **DB-S5** | optimistic + polish (G5+G8+G9+G10) | — | accept ไม่เดา role (refetch stats หรือไม่ ++), avatar ผ่าน `useAvatar`, ฟอร์แมต `created_at`, error/empty state ระดับหน้า | ⚪ |
| **DB-S6** | ตรวจจอจริง 375/768/1280 | DB-S1..S5 | banner `h-52` absolute ไม่ทับ stats, การ์ดไม่ล้น, touch target ครบ | ⚪ |
| **DB-S7a** | กลไก `last_seen` (Q1) | — | ตรวจว่ามีคอลัมน์/middleware เก็บ `last_seen` อยู่แล้วไหม · ถ้าไม่มี → migration เพิ่มคอลัมน์ + middleware อัปเดตต่อ authenticated request (throttle ไม่ให้เขียน DB ทุก hit) | ⚪ |
| **DB-S7b** | การ์ด "ใช้งานวันนี้" (G1) | DB-S7a | endpoint นับสมาชิก academy ที่ `last_seen` = วันนี้ + ต่อการ์ดให้อ่านค่าจริง | ⚪ |
| **DB-S8** | รวม signal ชุดเล็ก (G11, Q2) | DB-S1 | endpoint รวมตัวเดียว (เลียน `reports/summary` #21) คืน: การเงินรอตรวจ (#23 `donations.pending`) + เข้าเรียนวันนี้ (#18) + ผลการเรียนค้างกรอก (#19) + การ์ด/แผง FE ตามสิทธิ์ key ปลายทาง | ⚪ |
| **DB-S9** | note widget builder dormant (G12, Q3) | — | ไม่มีโค้ด — ทำ note ใน §2/README ว่า `DashboardWidgetController` dormant ไม่ผูกกับหน้านี้ | ⚪ |
| **DB-S10** | เทสต์ | DB-S1..S8 | เทสต์ gate หน้า + endpoint รวม signal + endpoint last_seen บน MySQL จริง `phpunit.mysql.xml` | ⚪ |

**Rule:** ทุก step ต้อง verify (build/test/จอจริง) ก่อนขึ้น 🟢 · รายงาน agy/codex เชื่อไม่ได้ ต้อง `git diff --stat` + diff เอง

## 8. Codex Prompt Template (ต่อ step)
```
Context: .agents/school-admin/24-dashboard.md §DB-S<n>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): ui/pages/academies/[name]/admin/index.vue (+ composable/endpoint ถ้าระบุ)
Task: <สิ่งที่ codex ต้องทำตาม step>
Constraints:
  - mobile-first บังคับ (class ไม่มี prefix = มือถือ, touch 44px, ตรวจ 375px)
  - ห้ามแก้ scope อื่นนอก step
  - การ์ด/ทางลัดต้อง gate ด้วย can() ของ key ปลายทาง
Verification: npm run build (เจ้าของรันเอง) + php artisan test -c phpunit.mysql.xml --filter=<...>
Report back: diff สรุป + ผลเทสต์ (เลข assert จริง)
```

## 9. Review Log
- **2026-10-15** — audit ขั้น [1]+[2] เสร็จ (Claude): สแกนโค้ดจริง, ยืนยันไม่มีหน้าซ้ำ, widget รายได้ไม่ใช่ leak (endpoint gate finance.view จริง), พบ 10 gap (G1–G10) + 2 ทิศทาง (G11/G12) + 1 หนี้ข้ามเมนู · แตก DB-S1..S10 (คลื่น A ส่งได้เลย · คลื่น B รอ Q1–Q3) · ยังไม่ส่ง step ให้ agy
- **2026-10-15** — เจ้าของเคาะ Q1–Q3: **Q1=endpoint ใหม่นับ `last_seen`** (⇒ DB-S7 แตกเป็น S7a กลไก last_seen + S7b การ์ด) · **Q2=ชุดเล็ก action-ค้าง** (การเงินรอตรวจ+เข้าเรียนวันนี้+ผลการเรียนค้าง ⇒ DB-S8) · **Q3=ปล่อย widget builder ไว้ dormant** (⇒ DB-S9 เหลือแค่ note) · ทุก step ปลดล็อกแล้ว พร้อมส่ง agy (เริ่มคลื่น A: DB-S1..S6)
