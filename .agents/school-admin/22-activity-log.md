# 22 — ประวัติกิจกรรม (Member Activity Log)

> ไฟล์รองของเมนู `22 ประวัติกิจกรรม` (`admin/activity-log/` · `reports.view`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> สถานะ: 🟡 audit [1]+[2] + เคาะ Q1–Q3 + โค้ด AL-S1..S6 เสร็จ 2026-10-14 (คลาวด์ · php -l ผ่าน · เจ้าของรันเทสต์/build)

### เคาะแล้ว 2026-10-14
- **Q1 = polish + เพิ่มฟีเจอร์** (G1/G2/G3 + G5: drill-down · กราฟแนวโน้ม · ท็อปผู้ใช้ · export · ค้นหาผู้ทำ/เป้าหมาย)
- **Q2 = รวม "ประวัติการแก้ไข entity" (AuditLog) เข้า #22** → แท็บที่สอง + migration `audit_logs.academy_id`
- **Q3 = แก้ photo-path เฉพาะเมนูนี้** → avatar ผ่าน `useAvatar.getAvatarUrl`

---

## 1. Scope & Purpose

ไทม์ไลน์ประวัติกิจกรรมของ **สมาชิกในโรงเรียน** (อนุมัติ/ปฏิเสธ/เปลี่ยนบทบาท/ระงับ/ลบ/จัดฝ่าย ฯลฯ)
สำหรับผู้บริหาร/นายทะเบียน (ผู้ถือ `reports.view` หรือ `members.view`) ใช้ติดตามว่าใครทำอะไรกับสมาชิกคนไหนเมื่อไหร่

**ผู้ใช้:** owner · admin · director · registrar (ได้ `reports.view`) · ผู้ถือ `members.view`

> หมายเหตุขอบเขต: เมนูนี้ = **MemberActivityLog** (ประวัติกิจกรรมสมาชิก) เท่านั้น
> ส่วน **AuditLog** (ประวัติการ "แก้ไข" entity ทั่วไป — ชื่อ/ที่อยู่/การตั้งค่า) เป็นคนละระบบ
> โผล่ใน **เมนู #8** (`AuditLogTab`) + platform admin · เส้น academy เหลือแค่ `audit-logs/entity` (gate `students.view`)

---

## 2. Current State (จากการสแกนโค้ดจริง)

### Frontend
- **Page:** [`ui/pages/academies/[name]/admin/activity-log/index.vue`](../../ui/pages/academies/[name]/admin/activity-log/index.vue) (370 บรรทัด)
  - stats 4 การ์ด (ทั้งหมด/สมาชิกใหม่=approve/เปลี่ยนบทบาท=role_change/ถูกระงับ=suspend)
  - filter: action dropdown + from/to date · timeline (performer → description → target + avatar) · pagination
  - เรียก 3 endpoint: `activity-log` (list) · `activity-log/statistics` · `activity-log/actions`
  - **ดึง `$api` จาก `useNuxtApp()` ตรง ๆ** + โหลด academy ด้วยชื่อ (`loadAcademyInfo` → GET `/academies/{name}`) เพื่อหา id
- **เมนู:** `admin.vue` บรรทัด 272–274 โชว์ "ประวัติกิจกรรม" เมื่อ `can('reports.view')`
- **component ที่เกี่ยวข้อง (คนละเมนู):** `components/school/AuditLogTab.vue` (ใช้ใน #8) · `components/academy/departments/ActivityTab.vue`

### Backend
- **MemberActivityLogController** [`app/Http/Controllers/Api/Learn/Academy/MemberActivityLogController.php`](../../api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/MemberActivityLogController.php) (285 บรรทัด)
  - `index(Academy, Request)` → `forAcademy()` scope + eager-load user/targetUser (กัน N+1) + orderBy desc
    · filter action/category/user/target/**department**/date · default `recent(30)` · paginate (per_page cap 100)
    · คืน `{ success, logs:[...], pagination }`
  - `getStatistics` → `{ success, statistics:{ total, by_action, by_day, active_users, period_days } }` (หน้าต่าง 30 วัน default)
  - `getMemberLogs(Academy, memberId)` → ประวัติราย member (tenant-check member ก่อน) · **FE ยังไม่เรียกใช้**
  - `getAvailableActions()` → รายการ action label (ไม่มี {academy} · ไม่แตะข้อมูลโรงเรียน)
- **Routes** [`routes/learn/academy.php`](../../api/nuxnanravel/routes/learn/academy.php) บรรทัด 385–396
  - `activity-log/actions` → `auth:api` (ตั้งใจ · คืนแค่ชื่อ action)
  - group `{academy}/activity-log` → **`academy.permission:members.view,reports.view`** ✅ (index/statistics/member)
  - แยกต่างหาก: `{academy}/audit-logs/entity` → `academy.permission:students.view` (AuditLogController · ประวัติการแก้ไข entity)
- **Model:** `MemberActivityLog` (scope `forAcademy`/`byAction`/`byCategory`/`byUser`/`byTargetUser`/`recent`) · มี accessor `description`/`icon`/`color`/`action_category`

### Database
- `member_activity_logs` (academy_id · user_id performer · target_user_id · academy_member_id · action · old_values/new_values json · created_at)
- หมายเหตุประวัติ: เคยมี drift (จริง ๆ คือ `Schema::drop` pollute suite) แก้ไปแล้ว (worklog backlog)

### สิทธิ์
เส้นข้อมูลต้องมี `members.view` **หรือ** `reports.view` (APPROVED member) · เมนูโชว์ด้วย `reports.view`

---

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | ไทม์ไลน์ประวัติ + filter action/date + pagination | ✅ | ครบ ใช้งานได้ |
| 2 | ด่านสิทธิ์ + tenant isolation | ✅ | `forAcademy()` ทุกเมธอด · gate members.view/reports.view (G29 ปิดแล้ว) |
| 3 | กัน N+1 | ✅ | eager-load user/targetUser |
| 4 | stats การ์ด (total/approve/role_change/suspend) | ⚠️ | ตัวเลขเป็นหน้าต่าง 30 วัน แต่ label บอก "ทั้งหมด" (G3) |
| 5 | dark mode / theme | ❌ | หน้า hardcode สีสว่าง ไม่มี `dark:` (G2) |
| 6 | เรียก API ผ่าน useApi + inject academyId | ❌ | ใช้ `$api` ตรง + refetch academy by name (G1) |
| 7 | drill-down ประวัติรายคน | ❌ | backend `getMemberLogs` มี แต่ FE ไม่เรียก (G5) |
| 8 | visualize by_day / active_users | ❌ | backend คืนมา แต่ FE ไม่ใช้ (G5) |
| 9 | export ประวัติ | ❌ | ไม่มี (platform admin มี · academy ไม่มี) (G5) |
| 10 | avatar แสดงถูก | ⚠️ | ใช้ `profile_photo_path` (relative) เป็น `:src` → อาจแตก (G4 · หนี้ photo-path ทั้งระบบ) |

---

## 4. Permission Matrix

| Permission key | Owner | Admin | Director | Registrar | Teacher | Staff | Student | Guardian |
|---|---|---|---|---|---|---|---|---|
| เข้าเมนู (`members.view` OR `reports.view`) | ✅ | ✅ | ✅ | ✅ | ⚠️(members.view เท่านั้น) | ❌ | ❌ | ❌ |

> teacher มี `members.view` → ยิง endpoint ผ่าน แต่เมนูโชว์ด้วย `reports.view` เท่านั้น (teacher ไม่เห็นลิงก์) — เข้าได้เฉพาะ deep-link

---

## 5. Gap Analysis

- **G1 🟡 (FE convention):** หน้าใช้ `useNuxtApp().$api` + โหลด academy ด้วยชื่อ (`GET /academies/{name}`) เพื่อหา id
  แทนที่จะ `inject('academyId')` + `useApi()` แบบหน้า admin อื่น ⇒ ยิง request เกิน + ผิดแนว CLAUDE.md ("ไม่ใช้ $fetch/$api ตรง")
- **G2 🟠 (dark mode):** markup hardcode `bg-white` / `text-gray-900` / `text-gray-500` ไม่มี `dark:` variant เลย
  ⇒ โหมดมืดอ่านไม่ออก/ขาวโพลน (ส่วนอื่นของ admin รองรับ dark) — ผิดมาตรฐาน UI โปรเจค
- **G3 🟡 (mislabel stats):** การ์ด "กิจกรรมทั้งหมด" = `stats.total` ซึ่งเป็นยอด **30 วันล่าสุด** (getStatistics default days=30)
  ไม่ใช่ทั้งหมดจริง · การ์ด approve/role_change/suspend ก็เป็นหน้าต่าง 30 วัน — label ควรบอกช่วงเวลา
- **G4 🟡 (avatar path):** log avatar bind `profile_photo_path` (relative) ตรง ๆ เป็น `:src` → รูปแตกถ้าไม่ใช่ full URL
  (หนี้ photo-path ทั้งระบบ ดู `.agents/photo-path-migration-plan.md`) — ตัดสินแก้เฉพาะจุดหรือรอ migration รวม
- **G5 ⚪ (feature gap / nice-to-have):**
  - `getMemberLogs` (drill-down รายคน) มี backend แต่ FE ไม่มีทางคลิกเข้า
  - `by_day` (กราฟแนวโน้ม) + `active_users` (ท็อป 5 ผู้ใช้) คืนมาแต่ไม่แสดง
  - ไม่มี export (csv/xlsx) ของประวัติ academy · ไม่มีช่องค้นหาตามผู้ทำ/เป้าหมาย (backend รองรับ user_id/target_user_id)
- **G6 🟢 (verified good — ไม่ใช่ gap):** tenant leak เดิม (G29) ปิดแล้ว · scope `forAcademy()` ครบ · กัน N+1 · filter ครบ
- **G7 🟡 (ขอบเขต):** "ประวัติการแก้ไข" (AuditLog entity trail) เป็นคนละระบบ อยู่ที่ #8/platform — เคาะว่า #22 ควรรวมด้วยไหม

> สรุป: เมนูนี้ **ปลอดภัยและทำงานได้** finding เป็น polish ฝั่ง FE (dark mode + convention + label) + feature เสริม ไม่มีช่องโหว่ P0

---

## 6. Implementation Tasks (รอเจ้าของเคาะ Q)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| AL-S1 | G2 — dark mode + mobile-first polish | Q1 | ใส่ `dark:` variant ครบ · touch target 44px · ไม่มีเลื่อนแนวนอน | 🟢 โค้ดเสร็จ |
| AL-S2 | G1 — ใช้ useApi + inject academyId | Q1 | เปลี่ยน `$api`→`useApi` · inject academyId (ตัด refetch by name) | 🟢 โค้ดเสร็จ |
| AL-S3 | G3 — label ช่วงเวลา | Q1 | การ์ดบอก "(30 วัน)" + หัวกราฟ "30 วันล่าสุด" | 🟢 โค้ดเสร็จ |
| AL-S4 | G5 — ฟีเจอร์เสริม | Q1 | export xlsx/csv (BE+FE) · กราฟ by_day · ท็อป active_users · drill-down/ค้นหาผู้ทำ-เป้าหมาย (filter user_id/target_user_id) | 🟢 โค้ดเสร็จ |
| AL-S5 | G4 — avatar path | Q3 | avatar ทุกจุดผ่าน `useAvatar.getAvatarUrl` | 🟢 โค้ดเสร็จ |
| AL-S6 | G7/Q2 — รวม audit entity trail | Q2 | migration `audit_logs.academy_id` + service เติมค่า + `GET /{academy}/audit-logs` + แท็บ "ประวัติการแก้ไข" | 🟢 โค้ดเสร็จ |
| AL-S7 | เทสต์ + verify | ทุก step | `AcademyAuditLogTest` (scope+export gating) + build + ตรวจจอ | ⚪ รอเจ้าของ |

**Rule:** งาน UI ทุกชิ้นบังคับ mobile-first + dark mode · verify build + ตรวจจอ ก่อนขึ้น 🟢

### หมายเหตุการตัดสินใจ
- **drill-down (G5):** ทำเป็น "คลิกชื่อผู้ทำ/เป้าหมาย → โฟกัส filter `user_id`/`target_user_id` ของ index เดิม" (ไม่ใช้ `getMemberLogs`
  เพราะ log มี `user.id` ไม่ใช่ member id) → ได้ทั้ง drill-down + ค้นหาผู้ทำ/เป้าหมายในกลไกเดียว ไม่ต้องเพิ่ม endpoint
- **Q2 ข้อจำกัดสถาปัตยกรรม:** `audit_logs` เดิมไม่มี `academy_id` → เพิ่มคอลัมน์ **nullable** (ปลอดภัย/ย้อนกลับได้ ไม่ backfill)
  · `AuditLogService::log()` เติมค่าให้ log ใหม่จาก `entity->academy_id` อัตโนมัติ · **log เก่า = null → ไม่โผล่ในแท็บ**
  (ประวัติการแก้ไขเริ่มนับจากนี้ · backfill log เก่า = งาน owner-gated แยก ถ้าต้องการย้อนหลัง)

### ค้างเจ้าของ verify (AL-S7)
- `php artisan migrate` (เพิ่ม `audit_logs.academy_id`) · `php artisan test -c phpunit.mysql.xml --filter=AcademyAuditLog` · `./vendor/bin/pint`
- `npm run build` + ตรวจจอ 375/768/1280 (กราฟ by_day overflow-x · แท็บ · export)

## 7. Agy Prompt Template (ต่อ step)
```
Context: .agents/school-admin/22-activity-log.md §AL-S<n>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): <list>
Task: <what agy should do>
Constraints: (UI) mobile-first + dark mode block · useApi (ห้าม $api ตรง) · Pint
Verification: npm run build / 375px / (BE) php artisan test -c phpunit.mysql.xml --filter=...
Report back: diff summary + test output (claude ตรวจ diff เอง)
```

## 8. Review Log
- **2026-10-13 audit [1]+[2]** — Claude สแกน FE (`activity-log/index.vue` 370 บรรทัด + เมนู admin.vue) + BE
  (`MemberActivityLogController` 285 บรรทัด · routes activity-log/audit-logs · MemberActivityLog scopes)
  → โมดูลแข็งแรง (G29 ปิด tenant leak แล้ว · tenant scope + N+1 + filter ครบ) · finding G1–G5 เป็น FE polish/feature + G7 ขอบเขต
  · ไม่มีช่องโหว่ P0 · รอเจ้าของเคาะ Q1–Q3
- **2026-10-14 เคาะ Q1–Q3 + ลงมือ AL-S1..S6** (Claude เขียนในคลาวด์ · php -l ผ่าน · เจ้าของรันเทสต์/build)
  - **BE:** migration `audit_logs.academy_id` (nullable/reversible) · `AuditLog` fillable+scopeForAcademy · `AuditLogService::log()` เติม academy_id อัตโนมัติ
    · `AuditLogController::academyIndex` + route `GET /{academy}/audit-logs` (reports.view) · `MemberActivityLogController::export` + `MemberActivityLogExport` + route export
  - **FE:** เขียน `activity-log/index.vue` ใหม่ — useApi+inject (G1) · dark mode ครบ (G2) · label "(30 วัน)" (G3)
    · 2 แท็บ (กิจกรรมสมาชิก / ประวัติการแก้ไข) · กราฟ by_day · ท็อป active_users · ปุ่ม export Excel/CSV
    · คลิกชื่อ→โฟกัส user_id/target_user_id (drill-down+ค้นหา) · avatar ผ่าน `useAvatar` (G4)
  - **เทสต์:** `AcademyAuditLogTest` (academyIndex tenant-scope · export 200 · non-member 403 ทั้งสองเส้น)
  - เหลือ AL-S7 เจ้าของ verify (migrate + test MySQL + build + ตรวจจอ)
