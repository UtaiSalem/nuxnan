# 23 — รายได้ (Academy Revenue)

> ไฟล์รองของเมนู `23 รายได้` (`admin/revenue.vue` · `finance.view` / `finance.manage`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> สถานะ: 🟡 audit [1]+[2] เสร็จ 2026-10-14 · security แข็งแรง · เจอ authz inconsistency (finance.* vs isAdmin) · รอเจ้าของเคาะ Q1–Q3

---

## 1. Scope & Purpose

จัดการรายได้ของโรงเรียน: **การสนับสนุน/บริจาค** (แต้ม+เงินสด) · **แคมเปญโฆษณา** · **บัญชีแต้ม** (point account)
+ ประวัติการจัดการ · สำหรับ owner/admin/ฝ่ายการเงิน (ผู้ถือ `finance.view`/`finance.manage`)

> หมายเหตุขอบเขต: #23 ปัจจุบันครอบคลุมเฉพาะ **donations/campaigns/points** (โมดูลจากโปรเจค monetization)
> ส่วนการเงินอื่น (fee-structures/tuition-fees/payments/expenses/budgets — gate `finance.view` เหมือนกัน) อยู่ใน **เมนู #8**
> (school-management) ไม่ใช่ #23 · "Phase 1 done" = โครง donations/campaigns/points เสร็จ · ยังไม่เคยผ่าน menu-loop audit

---

## 2. Current State (จากการสแกนโค้ดจริง)

### Frontend
- **Page:** [`ui/pages/academies/[name]/admin/revenue.vue`](../../ui/pages/academies/[name]/admin/revenue.vue) (109 บรรทัด)
  - 4 แท็บ: ภาพรวมรายได้ · รายการสนับสนุน · แคมเปญโฆษณา · ประวัติการจัดการ
  - **มี page-level guard** ✅ (redirect ออกถ้าไม่มี `finance.view`/owner/admin) — ดีกว่า #21/#22
  - resolve academyId ด้วย `api.get('/api/academies/{name}')` (ไม่ inject — เหมือน #22 G1)
  - `visibleTabs` = โชว์ทุกแท็บถ้า `can('finance.view') || isOwner || isAdmin`
- **Components** (`ui/components/academy/revenue/`): `AcademyAdminRevenueWidget` · `AcademySupportReviewTable` · `AcademyCampaignManagementTable` · `AcademyRevenueActivity`
- **Composable:** [`useAcademyRevenue.ts`](../../ui/composables/useAcademyRevenue.ts) — `canViewRevenue=can('finance.view')` · `canManageRevenue=can('finance.manage')` (gate ปุ่มสร้าง/แก้/หยุดแคมเปญ)

### Backend
- **AcademyRevenueController** [`app/Http/Controllers/Api/Academies/AcademyRevenueController.php`](../../api/nuxnanravel/app/Http/Controllers/Api/Academies/AcademyRevenueController.php) (389 บรรทัด)
  - `supportSummary` (visibility:content) · `donations` (degrade: non-admin เห็นเฉพาะ approved/completed + public resource) · `revenue` (dashboard แต้ม/บริจาค/โฆษณา) · `revenueActivity`
  - `approveDonation`/`rejectDonation` · `campaignsIndex`/`campaignsStore`/`campaignsUpdate`
  - **ทุก method ที่แก้ข้อมูลเช็ก tenant** (`$donation->academy_id !== $academy->id` → 403) + **`$academy->isAdmin($user)` → 403**
- **Routes** [`routes/learn/academy.php`](../../api/nuxnanravel/routes/learn/academy.php) บรรทัด 1187–1199 (group `{academy}/revenue`)
  - reads → `finance.view` · writes (approve/reject/campaigns store/update) → `finance.manage` · support-summary → `visibility:content`
- **Service:** `AcademyDonateService` (approve/reject) · **Models:** `AcademyDonate` · `Advert` (campaign) · `AcademyPointAccount` · `AcademyPointTransaction`

### สิทธิ์
`finance.view`/`finance.manage` · `finance.reports` (director) · owner/admin bypass ทุกอย่าง

---

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | dashboard รายได้ (แต้ม/บริจาค/โฆษณา) | ✅ | `revenue()` ครบ |
| 2 | อนุมัติ/ปฏิเสธการสนับสนุน | ✅ | tenant-safe · แต่ต้อง isAdmin (G2) |
| 3 | จัดการแคมเปญโฆษณา (สร้าง/แก้/หยุด) | ✅ | tenant-safe · แต่ต้อง isAdmin (G1/G2) |
| 4 | ประวัติการจัดการ | ✅ | `revenueActivity()` |
| 5 | page guard + tenant isolation | ✅ | guard redirect + academy_id check ทุกจุด (G6) |
| 6 | สิทธิ์ `finance.view`/`finance.manage` ใช้ได้จริง | ❌ | API บังคับ `isAdmin` → finance.* (non-admin) ใช้ไม่ได้ (G1/G2) |
| 7 | dark mode / mobile-first | ✅ | หน้าหลัก dark-aware (ตรวจ component ย่อยตอน implement) |
| 8 | รวมการเงินอื่น (fee/tuition/expense/budget) | ⚠️ | อยู่ #8 ไม่ใช่ #23 — เคาะขอบเขต (G5) |

---

## 4. Permission Matrix (ตามที่ "ควรเป็น" เทียบกับ "เป็นจริง")

| การทำงาน | route gate | controller จริง | FE gate | ผล |
|---|---|---|---|---|
| ดู dashboard/donations/activity | `finance.view` | (degrade ตาม isAdmin) | tab: finance.view | ✅ ใช้ได้ |
| ดูแคมเปญ (`campaignsIndex`) | `finance.view` | **isAdmin** | tab: finance.view | 🔴 finance.view non-admin เห็น tab แต่ 403 (G1) |
| อนุมัติ/ปฏิเสธบริจาค | `finance.manage` | **isAdmin** | ปุ่ม: finance.manage | 🔴 finance.manage non-admin เห็นปุ่มแต่ 403 (G2) |
| สร้าง/แก้/หยุดแคมเปญ | `finance.manage` | **isAdmin** | ปุ่ม: finance.manage | 🔴 เหมือน G2 |

---

## 5. Gap Analysis

- **G1 🟠 (authz inconsistency — ฝั่งอ่าน):** `campaignsIndex` บังคับ `$academy->isAdmin()` ทั้งที่ route + FE tab gate ด้วย `finance.view`
  ⇒ ผู้ถือ `finance.view` ที่ไม่ใช่ admin **เห็นแท็บ "แคมเปญโฆษณา" แต่โหลดแล้ว 403** (ต่างจาก donations ที่ degrade graceful)
- **G2 🟠 (authz inconsistency — ฝั่งจัดการ · dead permission):** `approveDonation`/`rejectDonation`/`campaignsStore`/`campaignsUpdate`
  บังคับ `isAdmin` ไม่รับ `finance.manage` ⇒ **สิทธิ์ `finance.manage` ใช้ไม่ได้จริงกับคนที่ไม่ใช่ owner/admin** (มอบงานการเงินให้ฝ่ายไม่ได้)
  · FE โชว์ปุ่มตาม `canManageRevenue=can('finance.manage')` → กดแล้ว 403 · คลาสเดียวกับ EnrollmentPolicy G1 / #15
- **G3 🟡 (FE tab visibility):** `visibleTabs` โชว์ทุกแท็บถ้า finance.view — แต่แท็บแคมเปญต้อง isAdmin (API) → ต้องให้ tab/ปุ่มตรงกับ API
- **G4 🟡 (FE convention):** resolve academyId ด้วยชื่อ (`api.get('/api/academies/{name}')`) แทน `inject('academyId')` (เหมือน #22 · แต่ #23 มี guard แล้ว)
- **G5 ⚪ (ขอบเขตเมนู):** การเงินส่วน fee-structures/tuition-fees/payments/expenses/budgets (gate finance.view เหมือนกัน) อยู่ใน **#8**
  — เคาะว่า #23 รายได้ ควรรวมส่วนเหล่านี้ไหม หรือคงเฉพาะ donations/campaigns/points
- **G6 🟢 (verified good):** tenant isolation แน่น (academy_id check ทุก mutating method) · per-route gating ครบ · page guard · G18 (support-summary privacy) ปิดแล้ว · ไม่มีช่องโหว่ P0

> สรุป: **ปลอดภัย** (API เข้มกว่าที่ FE โฆษณา) แต่ **โมเดลสิทธิ์ขัดกัน** — finance.view/manage ถูกประกาศและโชว์ UI แต่ API ไม่รับ ทำให้ tab/ปุ่มพังสำหรับ non-admin และมอบงานการเงินให้ฝ่ายไม่ได้

---

## 6. Implementation Tasks (รอเจ้าของเคาะ Q)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| RV-S1 | G1/G2 — align authz กับ finance.* | Q1 | campaignsIndex→finance.view · approve/reject/campaigns write→finance.manage (คง tenant check) · เทสต์ | ⚪ รอ Q1 |
| RV-S2 | G3 — FE tab/ปุ่มตรงกับ API | Q1 | gate แท็บแคมเปญ + ปุ่มด้วยสิทธิ์จริงที่เคาะ | ⚪ รอ Q1 |
| RV-S3 | G4 — useApi inject academyId | Q1 | ใช้ inject('academyId') | ⚪ รอ Q1 |
| RV-S4 | G5 — รวมการเงินอื่น (ถ้าเอา) | Q2 | นำ fee/tuition/payments/expenses/budgets เข้า #23 | ⚪ รอ Q2 |
| RV-S5 | Phase 2 features (ถ้าเอา) | Q3 | export · กราฟรายได้ · filter donations/campaigns | ⚪ รอ Q3 |

**Rule:** งาน UI บังคับ mobile-first + dark mode · verify build + test ก่อนขึ้น 🟢

### ❓ Q ที่ต้องเคาะ
- **Q1 — แก้ authz (G1/G2) ทางไหน:** ให้ `finance.view` ดูแคมเปญได้ + `finance.manage` อนุมัติบริจาค/จัดการแคมเปญได้
  (= มอบงานการเงินให้ฝ่ายได้ · แนะนำ ตรงกับ route+FE+permission catalog) **หรือ** คงเป็น owner/admin เท่านั้น (แล้วแก้ FE ซ่อน tab/ปุ่มถ้าไม่ใช่ admin)?
- **Q2 — ขอบเขต #23:** รวมการเงินอื่น (fee-structures/tuition/payments/expenses/budgets ที่อยู่ #8) เข้ามาด้วยไหม หรือคงเฉพาะ donations/campaigns/points?
- **Q3 — Phase 2:** ต้องการฟีเจอร์เพิ่ม (export · กราฟรายได้ · filter) หรือปิด #23 ที่ parity หลังแก้ G1/G2?

## 7. Agy Prompt Template (ต่อ step)
```
Context: .agents/school-admin/23-revenue.md §RV-S<n>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): <list>
Task: <what agy should do>
Constraints: tenant check คงไว้ · (UI) mobile-first + dark mode · Pint
Verification: php artisan test -c phpunit.mysql.xml --filter=... / npm run build / 375px
Report back: diff summary + test output (claude ตรวจ diff เอง)
```

## 8. Review Log
- **2026-10-14 audit [1]+[2]** — Claude สแกน FE (`revenue.vue` + 4 components + `useAcademyRevenue`) + BE
  (`AcademyRevenueController` 389 บรรทัด · routes revenue group · AcademyDonate/Advert/PointAccount)
  → security แข็งแรง (tenant check ทุก mutating method + per-route gate + page guard + G18 ปิด) · ไม่มี P0
  · finding หลัก = **authz inconsistency** G1/G2 (API บังคับ isAdmin ขัดกับ finance.view/manage ที่ route+FE ใช้) + G3/G4 FE + G5 ขอบเขต
  · รอเจ้าของเคาะ Q1–Q3
