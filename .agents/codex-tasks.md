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

## BATCH C — DS7 เปิด guest บริจาคเงินสด (course/academy)

### TASK-C1 — guest cash donation (slip) + throttle + null-safe donor

```yaml
id: TASK-C1
assigned_to: codex
status: done
priority: high
type: backend
completed_at: 2026-07-25
completion_notes: |
  Cash donation routes (course+academy) moved to throttle:6,1 group (no auth); points stay authed.
  CourseDonatePolicy + AcademyDonatePolicy donate() now ?User (guest allowed if enabled + public;
  owner still blocked). createCashDonation ?User + donor_id => donor?->id + null-safe self-guard.
  New GuestDonationTest: guest cash 201/donor_id null, guest points 401, disabled/private 403,
  authed regression, throttle 429. Claude-verified diff + route:list + 38/38 green + Pint. Clean (no cruft).
```

**บริบท (DS7 — locked):** เปิดให้ผู้ใช้ที่ยังไม่ล็อกอิน (guest) บริจาค **เงินสด (slip) เท่านั้น** ให้ course/academy ได้ · **บริจาคแต้ม (points) ยังต้อง auth** (ใช้ pp ต้องมี user) · `donor_id` = null เมื่อ guest (schema nullable อยู่แล้ว) · beneficiary ล็อกจาก route param อยู่แล้ว · guest **โฆษณา** ไม่รวม (epic D4)

**⚠️ Security — endpoint ใหม่รับ upload แบบไม่ auth = ต้องกัน abuse:**
- เพิ่ม `throttle:6,1` (6 req/นาที/IP) ที่ route guest cash donation ทั้ง 2
- guest บริจาคได้เฉพาะ course/academy ที่ **donationEnabled() = true และเป็น public** (status ไม่ใช่ private) — บังคับผ่าน policy (guest บริจาคให้ห้อง/คอร์สส่วนตัวไม่ได้)
- slip ยังเก็บ private disk เหมือนเดิม (ไม่แตะ)

**จุดแก้ (อ่านทุกไฟล์ก่อน):**
1. `routes/earn/donate.php` — ย้าย 2 route ออกจาก group `['auth:api', jetstream, 'verified']` (บรรทัด ~15-29):
   - `POST /courses/{course}/donations/cash` (CourseDonationController@storeCash)
   - `POST /academies/{academy}/donations/cash` (AcademyDonationController@storeCash)
   - ไปไว้ใน group ใหม่ **ไม่มี auth** + `->middleware('throttle:6,1')` · **route อื่นคงเดิม** (points, mine, showFor, allocations, withdrawals ยัง auth)
2. `app/Http/Requests/CourseDonate/StoreCashDonationRequest.php` — `authorize()` ต้องผ่านสำหรับ guest: เรียก `app(CourseDonatePolicy::class)->donate($this->user(), $this->route('course'))` (policy รับ null ได้หลังแก้ข้อ 4)
3. `app/Http/Requests/AcademyDonate/StoreCashDonationRequest.php` — `authorize()` `Gate::allows('donate', $this->route('academy'))` ต้องผ่านสำหรับ guest (policy/gate รับ null — ดูข้อ 4)
4. **Policies null-safe:**
   - `app/Policies/CourseDonatePolicy.php` `donate(?User $user, Course $course)`: `return $course->donationEnabled() && ($course->status != 2 || ($user && ($user->id === $course->user_id || $course->members()->whereKey($user->id)->exists())));`
   - academy 'donate' gate/policy: **หาให้เจอ** (`grep -rn "function donate\|'donate'\|Gate::define" app/Policies app/Providers`) — ทำ signature `?User $user` + logic เดียวกัน (enabled + public academy หรือ member/owner; guest = public+enabled เท่านั้น)
5. **Services null-safe donor:**
   - `app/Services/CourseDonateService.php` `createCashDonation(?User $donor, ...)`: `donor_id => $donor?->id`
   - `app/Services/AcademyDonateService.php` `createCashDonation(?User $donor, ...)`: `donor_id => $donor?->id` + guard self-donation null-safe: `if ($donor && $donor->id === $academy->user_id)`
6. Controllers `storeCash` — `$r->user()` คืน `?User` อยู่แล้ว ไม่ต้องแก้ (แค่ยืนยัน service รับ null ได้)

**Tests (เพิ่มใหม่ ไฟล์ `tests/Feature/GuestDonationTest.php`):**
- guest POST cash → 201, `donor_id === null`, status pending
- guest POST **points** → 401 (ยัง auth)
- guest บริจาคให้ course/academy ที่ donation ปิด → 403
- authed user cash donation ยังทำงาน (regression)
- (ถ้าทำได้) เกิน throttle → 429

**Acceptance:**
- [ ] guest บริจาคเงินสด course+academy ได้ (donor_id null), points ยัง 401
- [ ] policy กัน guest บริจาคห้อง/คอร์ส private หรือ donation ปิด
- [ ] throttle:6,1 ที่ route guest
- [ ] เทสต์เดิม donation ทั้งหมดเขียว + เทสต์ guest ใหม่เขียว, Pint clean

---

## BATCH D — DS8 สร้าง AcademyPointWithdrawal (mirror CoursePointWithdrawal)

### TASK-D1 — academy withdrawal subsystem (pp → academy owner, no payout-proof)

```yaml
id: TASK-D1
assigned_to: codex
status: done
priority: high
type: backend
completed_at: 2026-07-25
completion_notes: |
  Full AcademyPointWithdrawal subsystem mirrored from course: migration (no payout_proof),
  model, service (pp->academy owner via earn 'academy_withdrawal', ledger to
  academy_point_transactions, maker-checker), owner+admin controllers, policy (registered in
  AppServiceProvider), 5 FormRequests (MarkPaid no proof), Resource, 9 routes, 3 new tx type consts.
  Claude-verify FIXED Codex migration typo (dangling $table stubs where payout_proof cols removed)
  + reverted Codex's stray latest-analysis.md edit.
  ⚠️ INCIDENT: Codex ran `php artisan migrate` on DEV DB → applied make_gamification_rules_xp_only
  (batch 108) despite "create-only" rule. Academy migration stayed Pending (syntax err blocked it).
  Awaiting user decision on rollback. Academy migration NOT run on dev.
  Tests: academy 18 (10 service + 8 endpoint) + course 18 = 36 green. Pint clean.
```

**บริบท (DS8):** โรงเรียนรับบริจาคเข้า `academy_point_account` แล้วต้องถอนตรงได้ (ไม่ต้อง allocate ลงวิชาก่อน) → สร้างระบบถอนของ academy **ขนานกับ course** จ่ายเป็น **pp เข้าเจ้าของโรงเรียน** (`academy->user_id`), **ไม่มี payout-proof** (DS5), **maker-checker คงไว้** (DS3/DS5)

**⚠️ MIGRATION: สร้างไฟล์เท่านั้น — ห้ามรัน `php artisan migrate` บน DB dev** (user รันเอง) · **รันเทสต์ได้** (RefreshDatabase migrate test DB แยก ไม่แตะ MySQL local)

**วิธีทำ: mirror ไฟล์ course → academy ตรงๆ แล้วปรับ delta ด้านล่าง** (อ่าน template course ก่อนทุกไฟล์)

**ไฟล์ template (course) → สร้างใหม่ (academy):**
| course template | academy ใหม่ |
|---|---|
| `database/migrations/2026_07_18_300001_create_course_point_withdrawal_requests_table.php` | `database/migrations/2026_07_25_000001_create_academy_point_withdrawal_requests_table.php` |
| `app/Models/CoursePointWithdrawalRequest.php` | `app/Models/AcademyPointWithdrawalRequest.php` |
| `app/Services/CoursePointWithdrawalService.php` | `app/Services/AcademyPointWithdrawalService.php` |
| `app/Http/Controllers/Api/Courses/CoursePointWithdrawalController.php` | `app/Http/Controllers/Api/Academies/AcademyPointWithdrawalController.php` |
| `app/Http/Controllers/Api/PlearndAdmin/CoursePointWithdrawalAdminController.php` | `app/Http/Controllers/Api/PlearndAdmin/AcademyPointWithdrawalAdminController.php` |
| `app/Policies/CoursePointWithdrawalPolicy.php` | `app/Policies/AcademyPointWithdrawalPolicy.php` |
| `app/Http/Requests/CoursePointWithdrawal/{Store…,Review,Approve,Reject,MarkPaid}Request.php` | `app/Http/Requests/AcademyPointWithdrawal/{Store…,Review,Approve,Reject,MarkPaid}Request.php` |
| `app/Http/Resources/CoursePointWithdrawal/CoursePointWithdrawalResource.php` | `app/Http/Resources/AcademyPointWithdrawal/AcademyPointWithdrawalResource.php` |
| `tests/Feature/CoursePointWithdrawalTest.php` + `…EndpointsTest.php` | `tests/Feature/AcademyPointWithdrawalTest.php` + `…EndpointsTest.php` |

**DELTA ที่ต้องเปลี่ยนจาก course:**
1. **Migration:** FK `academy_id`→academies, `academy_point_account_id`→academy_point_accounts, `academy_point_transaction_id`→academy_point_transactions · **ตัดคอลัมน์ `payout_proof_path/original_name/mime/size` ทิ้ง** (DS5) · เปลี่ยนชื่อ index/FK เป็น `apwr_` (ห้ามชนชื่อ course) · index `['academy_id','status','created_at']`, `['requested_by','created_at']`
2. **Model:** ตัด fillable/casts ของ `payout_proof_*` · relation `academy()`→Academy, `account()`→AcademyPointAccount(`academy_point_account_id`), `transaction()`→AcademyPointTransaction(`academy_point_transaction_id`) · statuses + `canTransitionTo` เหมือน course เป๊ะ
3. **AcademyPointTransaction (แก้ไฟล์เดิม):** เพิ่ม const `TYPE_WITHDRAWAL_RESERVE='withdrawal_reserve'`, `TYPE_WITHDRAWAL_RELEASE='withdrawal_release'`, `TYPE_WITHDRAWAL_PAID='withdrawal_paid'`
4. **Service:** source = `AcademyPointAccount::where('academy_id',…)` · **ledger เขียนลง academy_point_transactions ด้วยคอลัมน์จริง**: `academy_point_account_id`, `academy_id`, `user_id`, `type`, `amount`, `balance_before`, `balance_after`, `created_by`, `metadata` (⚠️ ไม่มี `course_id`/`course_point_account_id`) · request() ตรวจสิทธิ์ `$academy->user_id === $requester->id || $academy->isAdmin($requester)` · min = `AcademyPointAccount::MINIMUM_WITHDRAWAL`/`canWithdraw` · **markPaid: ตัด param `array $proofData`** (signature `(request, payer, ?paymentReference)`) จ่าย `points->earn($request->requester, $amount, 'academy_withdrawal', $request->academy_id, 'Academy withdrawal payout', ['academy_point_transaction_id'=>$tx->id])` · config `config('wallet.academy_withdraw.maker_checker_threshold', 5000)` + `config('wallet.academy_withdraw.maker_checker_disabled', false)` (ใช้ default ได้ ไม่ต้องแก้ config)
5. **Admin controller markPaid:** เรียก `service->markPaid($withdrawal, $r->user(), $r->input('payment_reference'))` (ไม่มี proof)
6. **MarkPaidRequest:** rules มีแค่ `payment_reference` (ไม่มี proof)
7. **Resource:** ตัด field proof/has_proof ทิ้ง
8. **Policy:** `viewAny(User,Academy)`, `view(User,AcademyPointWithdrawalRequest)`, `moderate` — mirror
9. **AppServiceProvider (แก้ไฟล์เดิม):** เพิ่ม `use App\Models\AcademyPointWithdrawalRequest;` `use App\Policies\AcademyPointWithdrawalPolicy;` + `Gate::policy(AcademyPointWithdrawalRequest::class, AcademyPointWithdrawalPolicy::class);` ใน boot() (ถัดจาก CoursePointWithdrawal)
10. **Routes (`routes/earn/donate.php`):**
    - owner (ใน group `['auth:api', jetstream, 'verified']`): `POST /academies/{academy}/withdrawals` (store), `GET /academies/{academy}/withdrawals` (index), `POST /academy-withdrawals/{withdrawal}/cancel`
    - admin (ใน group `[…'plearnd_admin']` prefix `/plearnd-admin/academy-withdrawals`): GET `/`, GET `/{withdrawal}`, PATCH `/{withdrawal}/review|approve|reject|mark-paid`
    - import controller ใหม่ทั้ง 2 ที่หัวไฟล์

**Tests:** mirror course withdrawal tests → academy (request/reserve, review→approve→mark-paid จ่าย pp เข้า owner, maker-checker กันคนเดียวทำหลาย step, reject/cancel release reserve, below-min 422). ใช้ AcademyPointAccount ที่มี balance พอ

**Acceptance:**
- [ ] migration ไฟล์ใหม่ (ไม่รันบน dev) · schema academy ครบ ไม่มี payout_proof
- [ ] owner ถอน → review → approve → mark-paid แล้ว pp เข้า academy owner, ledger academy_point_transactions ครบ (reserve/release/paid), reserved_balance/balance/total_withdrawn ถูกต้อง
- [ ] maker-checker กันคนเดียวทำ 2 บทบาทเมื่อเกิน threshold
- [ ] ไม่มี proof ทุกจุด · Policy ทำงาน (owner เห็นของตัวเอง)
- [ ] เทสต์ academy withdrawal ใหม่เขียว + course withdrawal เดิมยังเขียว, Pint clean

---

## BATCH E — DS2 verify ad delivery + revenue split

### TASK-E1 — verify ad pipeline (done by Claude — coverage already existed)

```yaml
id: TASK-E1
assigned_to: claude
status: done
priority: medium
type: verify
completed_at: 2026-07-25
```

**ผล:** pipeline โฆษณา (delivery → RewardDistribution → 60/25/10/5) **มี test coverage ครบอยู่แล้ว** และรันผ่าน — ไม่ใช่ greenfield ที่ไม่เคยรัน:
- `RewardDistributionTest` (course leg, academy leg, idempotent, budget cap, no-course fallback), `AdDeliveryHardeningTest`, `AdRevenueIntegrityTest`, `RevenueSharePolicyResolverTest`, `RevenueSharePolicyAdminTest` — **34 passed**
- Claude เพิ่ม 1 test: `test_distribute_credits_all_four_legs_and_conserves_value_for_course_ad_with_academy` — พิสูจน์ course ad ที่มี academy แจกครบ 4 ขา (student 60/course 25/academy 10/platform 5) + **value conservation** (credited รวม == gross ไม่หาย/ไม่เกิน) → RewardDistributionTest 9 passed, Pint clean

**🐞 FINDING (edge case — ยังไม่แก้ รอ user ตัดสิน):** `RewardDistributionService::distribute` เครดิต academy leg เฉพาะเมื่อ `academy_id !== null`. ถ้า course-scoped ad ของคอร์สที่**ไม่มี academy** (orphan course) → academy share (เช่น 1/10) ถูกคำนวณใน split แต่ไม่ถูกเครดิตใคร → **budget รั่ว ~10%** (advertiser ถูกหัก gross เต็มแต่แจกไม่ครบ). Production ปกติไม่โดนเพราะ `CampaignController::store` เซ็ต academy_id จาก course->academy_id ให้ course ad. ทางแก้ถ้าต้องการ: route academy share ที่ค้างไปเข้า platform (course account) เมื่อไม่มี academy target.

---

## BATCH F — Fix finding #2 (orphan-course ad revenue leak)

### TASK-F1 — conserve value: fold untargeted ad-revenue legs into platform

```yaml
id: TASK-F1
assigned_to: codex
status: done
priority: high
type: backend
completed_at: 2026-07-25
completion_notes: |
  distribute() now folds untargeted course/academy legs into platform residual (mutates $split
  so metadata stays consistent). Placed after $targetCourseId, before crediting. Resolver untouched.
  Updated 2 leaky tests (orphan course: academy 0/platform 2/platform_earned 2; academy-only:
  course 0/platform 3/platform_earned 3); 4-way conservation test unchanged. Clean diff.
  Claude-verified: ad/revenue suite 35 passed, Pint clean. Residual (ad with neither course nor
  academy = public/legacy) left as-is (out of DS2 scope, documented).
```

**บริบท:** `RewardDistributionService::distribute` เครดิต course leg เฉพาะเมื่อมี course target, academy leg เฉพาะเมื่อมี `academy_id` → ถ้า target ขาด (course ad ของคอร์สไม่มี academy / academy ad ไม่มี course) share ของขานั้นถูก**คำนวณแต่ไม่ถูกเครดิตใคร** ทั้งที่ budget ถูกหัก gross เต็ม → **budget รั่ว**. `complete()` เรียก distribute ทุก delivery ไม่ gate scope

**หลักการแก้:** ขาที่ไม่มีผู้รับ → **fold เข้า platform residual** (platform เป็น catch-all อยู่แล้ว: `platform = gross - student - course - academy`) เพื่อให้ credited รวม == gross เสมอ (value conservation)

**ไฟล์:** `app/Services/Campaign/RewardDistributionService.php` method `distribute()`

**จุดแก้:** หลังบรรทัดที่คำนวณ `$split = $this->resolver->split($gross, $policy);` และหลังกำหนด `$targetCourseId` (~บรรทัด 58) ก่อน block เครดิต — เพิ่ม **mutate `$split` ให้สอดคล้องกับสิ่งที่เครดิตจริง** (metadata reconciliation จะตรง):
```php
// Value conservation: any leg without a beneficiary target folds into the platform
// residual so the advertiser's full gross is always distributed (never silently lost).
if ($targetCourseId === null && $split['course'] > 0) {
    $split['platform'] += $split['course'];
    $split['course'] = 0;
}
if ($academyId === null && $split['academy'] > 0) {
    $split['platform'] += $split['academy'];
    $split['academy'] = 0;
}
```
- block เครดิตเดิมใช้เงื่อนไข `$split['course'] > 0` / `$split['academy'] > 0` อยู่แล้ว → ขาที่ถูก zero จะข้ามเอง, platform ที่โตขึ้นจะเข้า course account (ถ้ามี) มิฉะนั้น academy account — **ไม่ต้องแก้ logic เครดิตอื่น** แค่ให้ platform block ใช้ `$split['platform']` ที่โตแล้ว (ซึ่งเดิมก็ใช้ `$split['platform']` อยู่แล้ว)
- **ห้ามแตะ** `RevenueSharePolicyResolver::split` (คงสูตร) · ห้ามแตะ student leg

**⚠️ residual ที่ยอมรับ (note ไว้ ไม่ต้องแก้):** ถ้าโฆษณาไม่มีทั้ง course และ academy (เช่น public ad ที่หลุดเข้า pipeline นี้) platform ยังไม่มี account ให้ลง → ยังตกได้ แต่ **นอกสโคป DS2** (school ad มี course หรือ academy เสมอ; public ใช้ legacy path)

**Tests — ต้องอัปเดตเทสต์เดิมที่ encode พฤติกรรมรั่ว + คงเทสต์ conservation ใหม่:**
- `tests/Feature/RewardDistributionTest.php`:
  - `test_distribute_credits_student_course_and_platform_per_policy` (course, ไม่มี academy): หลัง fix → `splits['academy']` 1→**0**, `splits['platform']` 1→**2**, course account `platform_earned` 1→**2** (student 6 + course 2 คงเดิม) · เพิ่ม assert conservation ถ้าจะครบ
  - `test_distribute_credits_academy_when_advert_is_academy_scoped` (academy, ไม่มี course): course leg fold → `splits['course']` 2→**0**, `splits['platform']` 1→**3**, academy account `platform_earned` →**3** (student 6, academy balance 1 คงเดิม)
  - `test_distribute_credits_all_four_legs_and_conserves_value_for_course_ad_with_academy` (มีทั้งคู่): **ไม่มี fold → ต้องยังเขียวเหมือนเดิม** (6/2/1/1)
  - เทสต์ conservation/idempotent/budget-cap อื่นต้องยังเขียว

**Acceptance:**
- [ ] course ad ไม่มี academy → academy share เข้า platform_earned ของ course account (ไม่หาย); academy ad ไม่มี course → course share เข้า platform_earned ของ academy account
- [ ] 4-way (course+academy) ไม่เปลี่ยน (ไม่มี fold)
- [ ] `splits` ใน metadata สอดคล้องกับที่เครดิตจริง (leg ที่ไม่มี target = 0)
- [ ] RewardDistributionTest + AdRevenueIntegrityTest + AdDeliveryHardeningTest เขียวทั้งหมด, Pint clean

| TASK-F1 | done | Claude (fold logic verified; ad/revenue suite 35 green; Pint clean) |

**Batch F verify:** finding #2 fixed — RewardDistributionService folds untargeted course/academy legs into platform residual (value conservation). 2 leaky tests updated, 4-way unchanged. 35/35 ad-revenue tests, Pint clean. Residual: ad with no course AND no academy still drops platform (public/legacy, out of DS2 scope).

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

**Batch B verify:** DS1 support removal — Codex clean diff (shared ad plumbing preserved), but had commented-out 3 support tests instead of deleting; Claude removed the dead block. CampaignSystemTest 13/13 + AcademyRevenueTest 15/15 · Pint clean. **Committed 3ba3435c.**

| TASK-C1 | done | Claude (route:list split verified; strong guest tests 38/38; Pint clean) |

**Batch C verify:** DS7 guest cash donation — Codex clean (routes throttled, policies null-safe, strong GuestDonationTest). route:list confirms cash public+throttled, points authed. 38/38 across 5 donation files · Pint clean. No cruft this round. **Committed 8e70db9f.**

| TASK-D1 | done | Claude (fixed migration typo; 36/36 withdrawal tests; routes registered; Pint clean) |

**Batch D verify:** DS8 AcademyPointWithdrawal — 12 new files mirror course. Codex left a migration syntax error (dangling $table stubs) → Claude fixed. Academy tests 18 + course 18 = 36 green, Pint clean, 9 routes. Migration created but Pending (create-only honored for academy). **Committed d73556ed.** ⚠️ Codex ran make_gamification_rules_xp_only on dev DB (batch 108) unauthorized → **user chose rollback; Claude rolled back (point_rules restored 1/10/50); now Pending again.**

| TASK-E1 | done | Claude (34 existing ad/revenue tests pass + added 4-way conservation test = 9 in RewardDistributionTest) |

**Batch E verify:** DS2 ad pipeline — coverage already existed (34 tests pass); Claude added 4-way value-conservation test. Flagged edge-case budget leak for orphan-course ads (no academy). No migration.

**Last updated:** 2026-07-25 · **Updated by:** Claude — **ALL DS1–DS8 (Batches A–E) done.**
