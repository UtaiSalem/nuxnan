# 22 — ประวัติกิจกรรม (Member Activity Log)

> ไฟล์รองของเมนู `22 ประวัติกิจกรรม` (`admin/activity-log/` · `reports.view`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> สถานะ: 🟡 audit [1]+[2] เสร็จ 2026-10-13 · โมดูลแข็งแรง (security ปิดแล้ว) · finding เป็น FE polish · รอเจ้าของเคาะ Q1–Q3

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
| AL-S1 | G2 — dark mode + mobile-first polish | Q1 | ใส่ `dark:` variant ครบ · ตรวจ 375/768/1280 · touch target | ⚪ รอ Q1 |
| AL-S2 | G1 — ใช้ useApi + inject academyId | Q1 | เปลี่ยน `$api`→`useApi` · inject academyId (ตัด refetch by name) | ⚪ รอ Q1 |
| AL-S3 | G3 — label ช่วงเวลาให้ถูก | Q1 | การ์ดบอก "30 วันล่าสุด" หรือเพิ่มตัวเลือกช่วง | ⚪ รอ Q1 |
| AL-S4 | G5 — drill-down + visualize (ถ้าเอา) | Q1 | คลิก member → getMemberLogs · กราฟ by_day · ท็อป active_users | ⚪ รอ Q1 |
| AL-S5 | G4 — avatar path | Q3 | ใช้ full URL / useFiles — หรือรอ photo migration | ⚪ รอ Q3 |
| AL-S6 | G7 — รวม audit entity trail (ถ้าเอา) | Q2 | แท็บ/ลิงก์ไป AuditLog entity ใน #22 | ⚪ รอ Q2 |

**Rule:** งาน UI ทุกชิ้นบังคับ mobile-first + dark mode · verify build + ตรวจจอ ก่อนขึ้น 🟢

### ❓ Q ที่ต้องเคาะ
- **Q1 — ขอบเขตงาน:** แค่ polish (G2 dark mode + G1 convention + G3 label) พอ **หรือ** เพิ่มฟีเจอร์ด้วย
  (G5: drill-down รายคน · กราฟ by_day · ท็อปผู้ใช้ · export · ค้นหาผู้ทำ/เป้าหมาย)?
- **Q2 — ขอบเขตเมนู:** #22 ควรรวม "ประวัติการแก้ไข entity" (AuditLog ที่อยู่ #8) ด้วยไหม หรือคงเฉพาะ member activity?
- **Q3 — avatar (G4):** แก้ photo-path เฉพาะเมนูนี้ หรือรอ migration รูปทั้งระบบ?

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
