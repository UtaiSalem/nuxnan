# Codex Task List — School-Tier Monetization (DS1–DS8)

> แผนต้นทาง: [`school-tier-monetization.md`](./school-tier-monetization.md) §5b (ตัดสินครบ 8 มติ DS1–DS8)
> Pipeline: **Codex implement → agy review → Claude verify → commit** (branch `feat/monetization-hardening`)
> กฎ: อ่านไฟล์ก่อนแก้เสมอ · ห้าม `migrate:fresh` / แก้ `.env` / แตะ `vendor` · **ห้ามรัน migration บน DB จริง** (Claude รันเอง) · ทำทีละ task ตามลำดับ
>
> **ทำเฉพาะ task ที่ `status: pending`** — task ที่ `status: queued` คือ backlog batch ถัดไป อย่าเพิ่งแตะจนกว่า Claude จะปลดเป็น pending

---

## BATCH A — Quick backend fixes (low risk, isolated)

### TASK-A1 — #13 ปิด scope leak: `getMoreAdvertisings` ไม่กรอง scope

```yaml
id: TASK-A1
assigned_to: codex
status: done
priority: critical
type: backend
completed_at: 2026-07-25
completion_notes: getMoreAdvertisings now filters scope_type null/public (mirrors index). Codex-impl, Claude-verified.
```

**ปัญหา:** `AdvertController::getMoreAdvertisings()` คืน advert ทุกตัวที่ `status=1 && remaining_views>0` โดย **ไม่กรอง `scope_type`** → โฆษณา scope=course/academy รั่วเข้าฟีดสาธารณะ (`index()` กรองถูกอยู่แล้ว)

**ไฟล์:** `api/nuxnanravel/app/Http/Controllers/AdvertController.php` (method `getMoreAdvertisings`, ~line 49)

**สิ่งที่ทำ:** เพิ่ม scope filter แบบเดียวกับ `index()` เข้าไปใน query ของ `getMoreAdvertisings`:
```php
->where(function ($query) {
    $query->whereNull('scope_type')->orWhere('scope_type', 'public');
})
```

**Acceptance:**
- [ ] `getMoreAdvertisings` คืนเฉพาะ advert ที่ `scope_type` เป็น null หรือ `'public'`
- [ ] logic เดิม (`status=1`, `remaining_views>0`, order) คงไว้ครบ
- [ ] ไม่แตะ method อื่น

---

### TASK-A2 — DS6 แก้อัตราแปลงบริจาคเงินสด (course + academy) เป็น ×1080

```yaml
id: TASK-A2
assigned_to: codex
status: done
priority: high
type: backend
completed_at: 2026-07-25
completion_notes: Course+Academy cash approve now credit round(cash × config donation_pp_per_baht). Point donations untouched. 3 tests realigned to ×1080 by Claude (verify-side).
```

**ปัญหา:** บริจาคเงินสด (slip) ตอน approve เครดิต pp แบบ **1:1** (`(int) cash_amount`) ควรเป็น `cash × config('economy.donation_pp_per_baht')` (=1080) หัก spread 10% เหมือน public · **บริจาคแต้ม (point) คง 1:1 — ห้ามแตะ**

**ไฟล์ + จุดแก้:**
1. `api/nuxnanravel/app/Services/CourseDonateService.php` method `approve()` (~line 75):
   - เดิม: `->creditCoursePoints($d->course_id, $d->donor_id, (int) $d->cash_amount, null, [...])`
   - เป็น: จำนวน pp = `(int) round($d->cash_amount * config('economy.donation_pp_per_baht'))`
2. `api/nuxnanravel/app/Services/AcademyDonateService.php` method `approve()` (~line 75):
   - เดิม: `->creditFromCashDonation($d->academy_id, $d->donor_id, (int) $d->cash_amount, null, [...])`
   - เป็น: จำนวน pp = `(int) round($d->cash_amount * config('economy.donation_pp_per_baht'))`

**Acceptance:**
- [ ] ทั้ง 2 service คำนวณ pp = `round(cash_amount × config('economy.donation_pp_per_baht'))` (cast int)
- [ ] `createPointDonation` ของทั้ง 2 ไฟล์ **ไม่ถูกแตะ** (point ยัง 1:1)
- [ ] ไม่ hardcode 1080 — อ่านจาก config เท่านั้น

---

### TASK-A3 — P0#1 `createPointDonation` เซ็ต `remaining_points` (donor-mode claim พัง)

```yaml
id: TASK-A3
assigned_to: codex
status: done
priority: high
type: backend
completed_at: 2026-07-25
completion_notes: createPointDonation now sets remaining_points = points_amount (donor-mode claim fixed). Codex-impl, Claude-verified.
```

**ปัญหา:** `CourseDonateService::createPointDonation` สร้าง `CourseDonate` โดย**ไม่เซ็ต `remaining_points`** → คอลัมน์เป็น 0/null → donor-mode claim (`CoursePointCampaignController` อ่าน `course_donates.remaining_points`) มองไม่เห็นแต้มคงเหลือ → กดรับแบบผู้บริจาคพังทั้งหมด (คอลัมน์มีจริง migration `2026_07_23_180000_add_donor_view_to_course_claims.php`, อยู่ใน fillable แล้ว)

**ไฟล์:** `api/nuxnanravel/app/Services/CourseDonateService.php` method `createPointDonation` (~line 35, `CourseDonate::create([...])`)

**สิ่งที่ทำ:** เพิ่ม `'remaining_points' => $pointsAmount` ใน array ที่ `CourseDonate::create()` (donor เริ่มต้นมีแต้มคงเหลือ = ที่บริจาคทั้งก้อน)

**Acceptance:**
- [ ] point donation ใหม่มี `remaining_points === points_amount`
- [ ] ไม่แตะ cash donation / academy donation

---

### TASK-A4 — DS5 เอา payout-proof requirement ออกจาก course withdrawal markPaid

```yaml
id: TASK-A4
assigned_to: codex
status: done
priority: medium
type: backend
completed_at: 2026-07-25
completion_notes: Removed proof from MarkPaidRequest rules + controller no longer stores proof file (passes []). Service/columns/download/maker-checker untouched. Endpoints test realigned (proof removed) by Claude.
```

**บริบท (DS3/DS5):** ถอนรายวิชา = จ่าย pp เข้าเจ้าของ (ภายใน) การโอนเงินจริง+KYC+สลิป เกิดตอนเจ้าของถอน wallet→ธนาคาร (มี proof อยู่แล้วที่นั่น) → payout-proof ที่ course markPaid = **ซ้ำซ้อน** ให้เอาออก · **maker-checker คงไว้**

**สถานะปัจจุบัน:** `MarkPaidRequest.rules()` มี `'proof' => 'nullable|file|...'` (nullable อยู่แล้ว ไม่บังคับ) และ controller เก็บไฟล์ถ้ามี

**ไฟล์ + จุดแก้:**
1. `api/nuxnanravel/app/Http/Requests/CoursePointWithdrawal/MarkPaidRequest.php` — ลบ key `'proof'` ออกจาก `rules()` (เหลือแค่ `payment_reference`)
2. `api/nuxnanravel/app/Http/Controllers/Api/PlearndAdmin/CoursePointWithdrawalAdminController.php` method `markPaid` (~line 60) — ลบ block ที่ `$r->file('proof')` store ไฟล์; เรียก `$this->service->markPaid($withdrawal, $r->user(), $r->input('payment_reference'), [])` (ส่ง proofData ว่าง)

**สำคัญ — อย่าทำเกิน:**
- [ ] **ห้าม** ลบคอลัมน์ `payout_proof_*` ออกจาก DB/model (migration ยุ่งยาก + admin UI/endpoint download อาจอ้างถึง) — แค่เลิก "รับ" proof ใหม่
- [ ] **ห้าม** แตะ signature `CoursePointWithdrawalService::markPaid` (ยังรับ `array $proofData` แต่เราส่ง `[]`) — คง maker-checker และ logic การจ่าย pp เดิมทั้งหมด
- [ ] endpoint download proof / resource fields เดิม คงไว้ (สำหรับรายการเก่าที่เคยแนบ)

**Acceptance:**
- [ ] `MarkPaidRequest.rules()` ไม่มี `proof`
- [ ] controller `markPaid` ไม่ store ไฟล์ ส่ง `[]` เป็น proofData
- [ ] service, model, migration, policy, download endpoint ไม่ถูกแตะ

---

### TASK-A5 — P1#18 schedule `risk:scan` (FraudDetectionService ไม่เคยถูกรัน)

```yaml
id: TASK-A5
assigned_to: codex
status: done
priority: low
type: backend
completed_at: 2026-07-25
completion_notes: risk:scan (RiskScanCommand) scheduled dailyAt 03:00 in routes/console.php. Codex-impl, Claude-verified command exists.
```

**ปัญหา:** มี fraud/risk scan command แต่ไม่ถูก schedule → ไม่เคยรันอัตโนมัติ

**สิ่งที่ต้องทำก่อน (สำรวจ):**
1. ยืนยันว่ามี artisan command ชื่อ `risk:scan` จริง — `grep -rn "risk:scan\|RiskScan\|FraudDetectionService" app/Console routes/console.php`
2. หา schedule registry ของโปรเจค (Laravel 12): ดู `routes/console.php` (`Schedule::command(...)`) หรือ `app/Console/Kernel.php` (`schedule()` method) — ใช้ไฟล์ที่โปรเจคใช้จริง

**สิ่งที่ทำ:** เพิ่ม schedule ให้ `risk:scan` รันรายวัน เช่น `->dailyAt('03:00')` (ถ้าใช้ `routes/console.php`: `Schedule::command('risk:scan')->dailyAt('03:00');`)

**ถ้า command ไม่มีจริง:** เปลี่ยน `status: blocked` + `block_reason:` ระบุว่าไม่พบ command แล้วให้ Claude ตัดสิน (อาจต้องสร้าง command ก่อน)

**Acceptance:**
- [ ] `risk:scan` (หรือชื่อจริงของ command) ถูก schedule รายวัน
- [ ] `php artisan schedule:list` แสดง entry ใหม่ (Claude จะ verify)
- [ ] ไม่แตะ logic ของ FraudDetectionService

---

## BATCH B — DS1 ยุบ `campaign_type=support` (adverts = โฆษณาอย่างเดียว)

### TASK-B1 — ลบ support campaign type ทั้งระบบ

```yaml
id: TASK-B1
assigned_to: codex
status: done
priority: high
type: backend
completed_at: 2026-07-25
completion_notes: |
  Codex removed SUPPORT enum case, distributeSupport method, support branches in
  CampaignController::store/review (kept payWithWallet/markSlipPending + state machine),
  StoreCampaignRequest support validation, and AcademyRevenueController Rule::in.
  Claude verify caught Codex had COMMENTED OUT (not deleted) 3 support tests in
  CampaignSystemTest (lines 277-417) — deleted the dead block. grep clean.
  CampaignSystemTest 13/13, AcademyRevenueTest 15/15, Pint clean. DB had 0 support rows.
```

**บริบท (DS1):** `campaign_type=support` ทับซ้อนกับ `course_donates`/`academy_donates` และมีบั๊กจ่ายซ้ำ ~190% (แจก pp + wallet 70/20/10 พร้อมกัน) → ยุบทิ้ง เหลือแค่ `advertisement` · **ยืนยันแล้ว DB มี 0 support rows** (49 adverts เป็น advertisement ทั้งหมด) จึงลบ enum case ได้ปลอดภัย

**⚠️ กฎเหล็ก — ห้ามลบเกินขอบเขต:**
- **ห้ามลบ** `SupportPaymentService::payWithWallet` และ `markSlipPending` — เป็นกลไกจ่ายเงินของ **โฆษณา** ด้วย (`CampaignController::store` เรียกทั้งสองสำหรับ ad) ลบแล้วสร้างโฆษณาพัง
- **ห้ามลบ/เปลี่ยนชื่อ** class `SupportPaymentService` (churn imports) — แค่ลบ method `distributeSupport`
- **ห้ามลบ** คอลัมน์ `exchange_points`/`distributed_at` ใน DB (ปล่อยไว้, ค่าใหม่ = 0/null)
- คง state machine ใน `review()` (approve/reject/pause + refundWallet) — แค่เอา distribute ออก

**จุดแก้ (อ่านทุกไฟล์ก่อน):**
1. `app/Enums/CampaignType.php` — ลบ `case SUPPORT = 'support';` (เหลือ ADVERTISEMENT)
2. `app/Services/Campaign/SupportPaymentService.php` — ลบ method `distributeSupport()` ทั้งเมธอด (บรรทัด ~44-142) + ลบ imports ที่ใช้เฉพาะในนั้นถ้าเหลือค้าง (CampaignScopeType, CampaignType) — เก็บ payWithWallet/markSlipPending
3. `app/Http/Controllers/Api/Campaign/CampaignController.php`:
   - `store()`: `exchange_points` (บรรทัด ~117-119) `$type === SUPPORT ? ... : 0` → เหลือ `'exchange_points' => 0,` · ลบตัวแปร `$pointsRate` (บรรทัด ~98) ที่ตายแล้ว · เก็บ payWithWallet/markSlipPending branch (payment_method)
   - `review()`: ลบบรรทัด `$payments->distributeSupport($campaign);` (~220) · ถ้า `SupportPaymentService $payments` ไม่ถูกใช้ที่อื่นใน review() แล้ว ให้ลบ param ออกจาก signature ของ `review()` (store() ยังใช้ $payments อยู่ — คงไว้)
4. `app/Http/Requests/Campaign/StoreCampaignRequest.php` — ลบ 2 บล็อก `$type === 'support'` ใน `withValidator` (บรรทัด ~73-78) · `campaign_type` Rule::in(CampaignType::cases()) จะแคบเหลือ advertisement เองหลังแก้ enum
5. `app/Http/Controllers/Api/Academies/AcademyRevenueController.php` — **มี store path ที่ 2**: บรรทัด ~312 `Rule::in(['advertisement', 'support'])` → `Rule::in(['advertisement'])` · อ่านทั้งเมธอด store นั้น ถ้ามี logic เฉพาะ support (exchange_points/distributeSupport) ให้เอาออกด้วย

**หลังแก้ — grep ยืนยันไม่มี reference ค้าง:**
- `grep -rn "distributeSupport\|CampaignType::SUPPORT\|'support'\|\"support\"" app` → ต้องไม่เหลือใน campaign path (donation "support summary" เป็นคนละความหมาย ไม่ต้องแตะ)

**Tests:** รัน `php artisan test tests/Feature/Campaign/CampaignSystemTest.php tests/Feature/AcademyRevenueTest.php` — ถ้ามีเทสต์ support campaign จะพัง ให้ลบ/ปรับเทสต์นั้นให้สอดคล้อง (support ไม่มีแล้ว) **แต่ห้ามลบเทสต์ advertisement/state-machine**

**Acceptance:**
- [ ] สร้าง advertisement campaign ได้ปกติ (ทั้ง wallet + slip) — payWithWallet/markSlipPending ยังทำงาน
- [ ] ไม่มี code path สร้าง support campaign ได้อีก (ทั้ง 2 store)
- [ ] grep ไม่เหลือ reference support ใน campaign path
- [ ] CampaignSystemTest + AcademyRevenueTest เขียว, Pint clean

---

## BATCH C–E — Backlog (queued — อย่าเพิ่งแตะ)

- **BATCH C — DS7** เปิด guest บริจาค: ผ่อน auth ที่ slip path ของ course/academy donation + lock beneficiary ฝั่ง server (donor_id=null เมื่อ guest) — guest **โฆษณา** ไม่รวม (epic D4 แยก)
- **BATCH D — DS8** สร้าง `AcademyPointWithdrawal` (migration+model+service+controller+policy+FormRequests+routes) mirror `CoursePointWithdrawal`: จ่าย pp เข้า academy owner, ไม่มี payout-proof (DS5), maker-checker คงไว้ — **งานสร้างใหม่ชุดใหญ่**
- **BATCH E — DS2** verify ad delivery pipeline + revenue split (student 60/course 25/academy 10/platform 5) ด้วยข้อมูลทดสอบ (0 rows = ไม่เคยรันจริง) — เขียน feature test

---

## สถานะรวม (Claude update)

| Task | Status | Verified |
|------|--------|----------|
| TASK-A1 | done | Claude (diff + no advert test regressions) |
| TASK-A2 | done | Claude (3 donation tests green @ ×1080) |
| TASK-A3 | done | Claude (diff; donor-claim column set) |
| TASK-A4 | done | Claude (proof removed; endpoints test green) |
| TASK-A5 | done | Claude (RiskScanCommand exists; schedule added) |

**Batch A verify:** donation+withdrawal suite 48/48 pass · Pint clean on all 10 changed files · Codex hung on turn-finalize after applying 6 source edits (all correct); Claude finished 4 test alignments (forced by DS6/DS5) + task-file bookkeeping. **Committed 7e7dd110.**

| TASK-B1 | done | Claude (diff surgical; deleted commented-out support tests; 13+15 green; grep clean) |

**Batch B verify:** DS1 support removal — Codex clean diff (shared ad plumbing preserved), but had commented-out 3 support tests instead of deleting; Claude removed the dead block. CampaignSystemTest 13/13 + AcademyRevenueTest 15/15 · Pint clean.

**Last updated:** 2026-07-25 · **Updated by:** Claude (Batch B verified, ready to commit)
