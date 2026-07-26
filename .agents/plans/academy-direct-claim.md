# Plan: Academy-level Direct-Claim Donations (no campaign layer)

> สถานะ: **แผน / รอ approval** — Claude เขียน spec, ยังไม่ลงมือ (per [[plan-not-implement]])
> เจ้าของแผน: Utai
> ที่มา: session 2026-07-26 — user ยืนยันว่าการมี "campaign" คั่นในระดับโรงเรียนคือความซับซ้อนที่ไม่จำเป็น
> Related: [[public-tier-monetization]] · [[support-ux-unification]] · [[owner-can-self-donate]]

---

## 0. Design decision (locked 2026-07-26)

**ระดับโรงเรียน: บริจาคแล้วสมาชิกโรงเรียนกดรับตรงจากกอง — ไม่มี campaign layer**

- ระดับ **วิชา (course)** ยังคงใช้ campaign-based ตามเดิม (มติ 2026-07-23 ใน [[support-ux-unification]] — ใช้เฉพาะ course)
- ระดับ **โรงเรียน (academy)** ใช้ **direct-claim per-donation** เหมือน public tier
- เหตุผล: การกระจายแบบ granular (per-lesson / per-quiz) ทำที่ระดับวิชาอยู่แล้ว โรงเรียนไม่ต้องซ้อนอีกชั้น

**เศรษฐศาสตร์ (locked 2026-07-26 — แตกจาก public tier)**
- อัตราแปลง: `donation_pp_per_baht` = 1,080 (reuse)
- ค่ากดรับ: `claim_cost` = 270 pp (reuse)
- **Split 4 ทาง (academy-specific): 210 ผู้กด + 30 ผู้แนะนำ + 20 โรงเรียน + 10 แพลตฟอร์ม**
- **ผู้แนะนำไม่มี → 30 pp ตกเป็นของโรงเรียนทั้งหมด** (school 50 / platform 10) — school เป็น "host" ของ tier นี้
- เพดาน: `claim_cap_per_donation_per_day` = 5 · `claim_cap_total_per_day` = 20 — **นับรวมทุก tier** (public + academy) ต่อ user (phase 1 — ดู section 6)
- FIFO ต่อโรงเรียน: donation เก่าสุดถูก drain ก่อน, กันโดย pessimistic lock (`SELECT … FOR UPDATE`) ตอนหัก
- School share (20 หรือ 50) → เข้า `AcademyPointAccount` (ไม่ใช่ personal wallet ของเจ้าของ) — ต้องถอนผ่าน `AcademyPointWithdrawal` admin flow

**Per-baht math (donor 1 บาท = 1,080 pp = 4 claims):**
| ผู้รับ | pp/claim | pp/บาท | % |
|---|---|---|---|
| Claimer | 210 | 840 | 77.8% |
| Suggester (avg) | 30 | 120 | 11.1% |
| School | 20 | 80 | 7.4% |
| Platform | 10 | 40 | 3.7% |
Platform รวมทุกขา ~13.7% (3.7% claim + ~10% currency spread 1200→1080 = ยัง sustainable)

**Invariant ที่ user ย้ำ:** *"ทุกครั้งของการที่มีเงินเข้า-ออก จะต้องให้ Admin ตรวจสอบก่อนเสมอ"*
- แต้ม→แต้ม (donate points, claim, distribute) → **ไม่ผ่าน admin** (เงินไม่ได้ออกจากระบบ, ผ่าน ledger แล้ว)
- **สลิปโอน (cash donation) → ต้องรอ admin approve** ก่อน credit เข้ากอง (มีอยู่แล้ว)
- **การถอนเงิน (withdrawal) → ต้อง admin approve** (มี `AcademyPointWithdrawal` flow อยู่แล้ว — verify ว่ายัง gated)

---

## 1. ไฟล์ / โมเดล / route ที่เกี่ยวข้อง (อ่านก่อนแก้)

### Backend
- Model: `app/Models/AcademyDonate.php`, `AcademyPointAccount.php`, `AcademyPointTransaction.php`
- Service: `app/Services/AcademyDonateService.php` (create donation), `AcademyPointAccountService.php` (credit/debit)
- Controller: `app/Http/Controllers/Api/Academies/AcademyDonationController.php`
- Route: `routes/earn/donate.php` — บล็อก `/academies/{academy}/donations/*`
- Config: `config/economy.php` — reuse ทั้งชุด (ยังไม่แตะ)
- Reference (public tier): `app/Http/Controllers/Api/Earn/DonateController.php::getDonate` (คือกลไก direct-claim ต้นแบบ)

### Frontend
- Page: `ui/pages/academies/[name].vue`
- Widget: `ui/components/academy/revenue/AcademySupportCtaWidget.vue`, `AcademyWalletCard.vue`
- Modal: `ui/components/donation/AcademyDonationModal.vue` + `SupportDonationModal.vue`
- Composable ต้นแบบ: `ui/composables/useCoursePoints.ts` (สำหรับ claim UX pattern)

---

## 2. Data model changes

### 2.1 Migration: เพิ่ม `remaining_points` ให้ `academy_donates`
```
$table->unsignedBigInteger('remaining_points')->nullable()->after('points_amount');
```
- ตั้ง default = `points_amount` ตอน create donation (mirror ที่ course_donates ทำ)
- Backfill migration สำหรับ record ที่มีอยู่: `UPDATE academy_donates SET remaining_points = points_amount WHERE donation_type='point'`

### 2.2 Table ใหม่: `academy_donate_claims`
เก็บ history ว่าใครกดรับจาก donation ไหน กี่ครั้ง เมื่อไหร่ (เพื่อบังคับเพดาน + audit)
```
- id
- academy_donate_id  FK
- academy_id         FK (denormalized สำหรับ index-per-academy)
- claimer_id         FK users
- suggester_id       FK users nullable
- amount_claimer     unsigned int   (default 210)
- amount_suggester   unsigned int   (default 30, 0 ถ้าไม่มี suggester)
- amount_school      unsigned int   (default 20; = 50 ถ้าไม่มี suggester — ดูดซับส่วนของ suggester)
- amount_platform    unsigned int   (default 10 — คงที่ ไม่เปลี่ยน)
- claimer_transaction_id     FK academy_point_transactions
- suggester_transaction_id   FK academy_point_transactions nullable
- school_transaction_id      FK academy_point_transactions
- platform_transaction_id    FK academy_point_transactions
- claimed_at         timestamp
- INDEX (academy_donate_id, claimer_id, claimed_at)  — บังคับ 5/day/donation
- INDEX (claimer_id, claimed_at)                     — บังคับ 20/day/total (per-tier)
```

Invariant: `amount_claimer + amount_suggester + amount_school + amount_platform = 270` เสมอ (assert ใน service)

### 2.3 ไม่ต้องเปลี่ยน: `AcademyPointAccount`, `AcademyPointTransaction`
ใช้ transaction types ใหม่:
- `donation_claim_debit` (หัก 270 pp ออกจาก `remaining_points` ของ donation)
- `donation_claim_reward_claimer` (+210 → claimer.pp)
- `donation_claim_reward_suggester` (+30 → suggester.pp, ถ้ามี)
- `donation_claim_reward_school` (+20 หรือ +50 → `AcademyPointAccount` ของโรงเรียนนี้)
- `donation_claim_reward_platform` (+10 → platform user `personal_code = 99999999`)

### 2.4 Config keys ใหม่ใน `config/economy.php`
เพิ่มชุด `academy_claim_*` แยกจาก public (เพื่อไม่ให้ public tier ที่ล็อคแล้วโดนกระทบ):
```
'academy_claim_cost' => 270,
'academy_claim_reward_claimer' => 210,
'academy_claim_reward_suggester' => 30,
'academy_claim_reward_school' => 20,
'academy_claim_reward_platform' => 10,
'academy_claim_cap_per_donation_per_day' => 5,
'academy_claim_cap_total_per_day' => 20,   // นับรวม cross-tier (ดู phase 1 rewrite)
```

---

## 3. Backend endpoints

### 3.1 `GET /academies/{academy}/donations/claimable` (auth:api)
คืน list donation ที่มี `remaining_points > 0` ในโรงเรียนนี้ + สถานะ per-user (กดไปกี่ครั้งวันนี้)
- Response: `{ donations: [{id, donor_display_name, donor_avatar, purpose, points_amount, remaining_points, my_claims_today, can_claim}], my_total_claims_today }`
- Filter: เฉพาะสมาชิกโรงเรียน (`AcademyMember::where('user_id', auth()->id())->where('academy_id', $academy->id)->exists()`)
- Order: `created_at ASC` (FIFO)

### 3.2 `POST /academies/{academy}/claim` (auth:api, throttle) — **ปุ่มเดียว FIFO auto**
กดรับ 270 pp — **backend เลือก donation หัวคิวเอง** (user ไม่ระบุ donation_id)
- Guard 1: user เป็นสมาชิกโรงเรียนจริง (`AcademyMember` status = active)
- Guard 2: เพดาน total (20/วัน cross-tier) — ยิง `429` + code `daily_cap_reached`
- Query: `academy_donates` where `academy_id=? AND donation_type='point' AND status='completed' AND remaining_points >= 270` ORDER BY created_at ASC LIMIT 1 FOR UPDATE
- ถ้าไม่พบ → `404` code `no_claimable_pool`
- Guard 3: เพดาน per-donation ต่อ user (5/วัน สำหรับ donation หัวคิวนั้น) — ถ้าเต็ม ให้ skip ไปตัวถัดไปในคิว (auto FIFO advance) จนกว่าจะเจอตัวที่ user ยังกดได้ หรือหมดคิว
- Atomic: `DB::transaction` — debit remaining_points 270, credit ledger 4 ทาง, insert `academy_donate_claims` row
- Ledger: 4 transaction rows (210 claimer + 30 suggester + 20 school + 10 platform หรือ 210+0+50+10 ถ้าไม่มี suggester)
- Response: `{ ok: true, claim: {...}, wallet: { pp: user.pp, delta: 210 }, next_available: bool }`

### 3.2.1 หา suggester ยังไง?
- ใช้ `users.suggester_id` (invited-by) — ถ้ามี user จริง → suggester ได้ 30
- ถ้า null หรือชี้ไป platform user (id=7) → ไม่มี suggester → 30 pp ตกเข้า school (school ได้ 50)

### 3.3 Policy: `AcademyDonatePolicy::claim(User $user, Academy $academy)`
- ต้องเป็นสมาชิกโรงเรียน active
- (donation-level guard เกิดใน service ตาม FIFO — ไม่ผ่าน policy)

### 3.4 `GET /academies/{academy}/claim/status` — ข้อมูลสถานะปุ่ม
Response: `{ pool_total_remaining, has_claimable, my_claims_today_academy, my_claims_today_all_tiers, cap_reached, next_donation_preview: {donor_display_name, remaining_points} }`
ใช้ render ปุ่ม + จำนวนที่เหลือของ user

### 3.5 **ไม่ทำ:** เอนดพ้อยท์ "distribute", "campaign", "reward-rule" ระดับโรงเรียน — เจ้าของโรงเรียนไม่มีปุ่มกระจายแต้ม เพราะกระจายอัตโนมัติผ่าน claim

---

## 4. Frontend changes

### 4.1 หน้า `pages/academies/[name].vue` (tab **revenue** สำหรับสมาชิก)
เพิ่ม card เดียว **"กดรับแต้มจากกองสนับสนุนโรงเรียน"** — วางเหนือ `AdvertiseCtaWidget`
- แสดง: ยอดกองรวม (`pool_total_remaining` pp) + จำนวนสิทธิ์คงเหลือวันนี้ (`20 - my_claims_today_all_tiers`) + preview ผู้บริจาคหัวคิว
- **ปุ่มเดียว** "กดรับ 210 แต้ม" — disabled + reason tooltip เมื่อ: ไม่มีกอง, เต็มเพดาน, ไม่ใช่สมาชิก
- Success: แสดง toast/inline "ได้รับ 210 pp จาก [donor]" + refresh สถานะ

### 4.2 Component ใหม่: `ui/components/academy/points/AcademyClaimWidget.vue`
- Props: `academyId`
- Emits: `claimed`
- Poll `GET /claim/status` เมื่อ mount และหลัง claim สำเร็จ

### 4.3 List ผู้บริจาค (transparency)
แยกเป็น section รอง: "ผู้สนับสนุนล่าสุด" — reuse pattern จาก `CourseSupportPanel` recent-supporters
- แสดง donor display name + amount + remaining_points + created_at
- ไม่มีปุ่มกดตรง card ผู้บริจาค — enforce ว่า claim ผ่านปุ่มเดียวที่ backend เลือก FIFO

### 4.3 Composable: `ui/composables/useAcademyPoints.ts`
- `fetchClaimable(academyId)`, `claim(academyId, donationId)`, reactive state

### 4.4 ปุ่ม "สนับสนุนโรงเรียน" — ไม่แตะ (donate flow เดิม)

### 4.5 ปุ่ม "ลงแคมเปญโฆษณา" — **ยังเก็บไว้** เพราะเป็น ad campaign (ต่างเรื่อง จะ tackle แยกในประเด็นถัดไป)

---

## 5. Memory / doc updates ที่ต้องทำ

- **Update** [[support-ux-unification]]: เพิ่ม paragraph ว่ามติ "no direct-from-pool claiming" ใช้กับ **course-level เท่านั้น**; academy = direct-claim
- **Update** [[public-tier-monetization]]: เพิ่มบรรทัดว่า academy inherits ทั้งชุด (270 split, caps 5/20) — คำนวณ margin เดียวกัน
- **Create** memory ใหม่ `project_academy_direct_claim.md` เมื่อเริ่ม implement — คำอธิบายสั้นๆ + ลิงก์มาที่ไฟล์แผนนี้

---

## 6. Rollout phases

### Phase 1 (backend) — Codex
1. Migration: `remaining_points` on `academy_donates` + create `academy_donate_claims` + backfill
2. **Cross-tier claim log** — สร้าง `user_claim_daily_counters` (หรือ view) รวม log จาก public tier (`donates` table) + academy tier — ให้ `academy_claim_cap_total_per_day=20` นับได้ครบ (per user decision phase 1)
3. Add `academy_claim_*` keys to `config/economy.php`
4. Update `AcademyDonateService::createPointDonation` → set `remaining_points = points_amount`
5. Add `AcademyClaimService` (new) — `preview()`, `claim()` methods, FIFO + lock + 4-way ledger + auto-advance on hit-cap
6. Add 2 endpoints (`POST /claim`, `GET /claim/status`) + policy method
7. Unit tests: FIFO drain, caps 5/20 (cross-tier), suggester=null fallback (school ได้ 50), insufficient remaining, concurrent-claim lock, invariant sum=270

### Phase 2 (frontend) — Codex
1. Composable
2. Card component
3. Mount ในหน้า academy revenue tab
4. Regression test: existing donate flow ไม่พัง

### Phase 3 (verify) — Claude
- Diff review + agy pass ([[codex-agy-pipeline]])
- Manual test: donate → member กดรับ → เพดาน 5 ต่อ donation ทำงาน → เพดาน 20/วันทำงาน → sufficient/insufficient scenarios
- Check `verify migrate:status` post-Codex ([[codex-runs-migrations]])

### Phase 4 (out of scope วันนี้)
- Course-level: ยัง campaign-based ตามเดิม
- Reconcile ledger gaps (P0 #8 จาก [[public-tier-monetization]])

---

## 7. Open questions — ทั้งหมด locked (2026-07-26)

1. ~~Cross-tier 20/day cap~~ → **phase 1**
2. ~~Anonymous donation~~ → **แสดง "ผู้ไม่ประสงค์ออกนาม" เสมอ** เมื่อ `anonymous=true` (ignore `donor_display_name`)
3. ~~สมาชิก pending/rejected~~ → **เฉพาะ `AcademyMember.status = 2` (approved/active)** + academy owner bypass (ไม่มี AcademyMember row แต่กดรับได้)
4. ~~ceiling 20/วัน~~ → **global per-user cap รวมทุก tier ทุกโรงเรียน**
5. ~~UI FIFO auto vs manual~~ → **ปุ่มเดียว FIFO auto**

**พร้อมส่ง Codex** → Phase 1 backend ตาม section 6

---

## 8. Non-goals (ยืนยันครั้งสุดท้าย)

- ❌ ไม่มี campaign editor สำหรับเจ้าของโรงเรียน
- ❌ ไม่มีการเนรมิต pp (ต้องมี donation เท่านั้น)
- ❌ ไม่แตะ course-level distribution
- ❌ ไม่เปลี่ยนค่า config/economy.php (reuse)
- ❌ ไม่ทำ direct claim จากสลิป cash โดยไม่ผ่าน admin (สลิปต้อง approve ก่อน credit เข้ากอง)
