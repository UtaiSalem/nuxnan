# Plan: เปลี่ยนโมเดล claim ระดับโรงเรียน — pp คงที่ → เปอร์เซ็นต์

> สถานะ: **แผน / รอ user execute** — Claude เขียน spec, user จะทำเอง
> ผู้เขียนแผน: Claude (session 2026-07-26)
> เป้าหมาย: ประหยัด token — user อ่านแล้วสั่ง Codex หรือแก้เองได้เลย
> Related: [[academy-direct-claim]] · [[public-tier-monetization]] · [[owner-can-self-donate]]

---

## 0. บริบท / เหตุผลที่ต้องเปลี่ยน

**ปัญหาปัจจุบัน:** โมเดล claim ระดับโรงเรียน (locked 2026-07-26) กำหนด
- ค่ากดรับ = **270 pp คงที่**
- Split = **210 / 30 / 20 / 10 pp** (claimer / suggester / school / platform)
- ผลข้างเคียง: donation ที่มี `remaining_points < 270` **กดรับไม่ได้เลย** — ค้างในกอง
- ต้องบังคับ **บริจาคขั้นต่ำ 270 pp** ที่ระดับ FormRequest + Service + UI modal
- คนที่มีแต้มน้อย (< 270) บริจาคไม่ได้ ลด incentive

**ข้อเสนอ user:**
1. **ยกเลิกขั้นต่ำ** — บริจาคกี่แต้มก็ได้ (min 1 pp)
2. **แบ่งเป็นเปอร์เซ็นต์** แทน pp คงที่ ทำให้ donation ก้อนเล็ก (< 270) ก็ยังกดรับได้
3. **สัดส่วนคงเดิม** ตามที่ล็อคไว้ (77.78% / 11.11% / 7.41% / 3.70%)

---

## 1. Model options — ต้องเลือก 1 ก่อนลงมือ

โจทย์: "หนึ่ง claim หัก pp จากกองเท่าไหร่?" มี 4 แนวคิด

### Option A — Fixed cost 270, small pool drains in one shot ✅ แนะนำ
- ถ้า `remaining >= 270` → หัก 270 (เหมือนเดิม)
- ถ้า `remaining < 270` → หัก `remaining` (drain ให้หมดในคลิกเดียว)
- Split ทุกครั้งใช้เปอร์เซ็นต์: claimer 77.78% / suggester 11.11% / school 7.41% / platform 3.70%
- ตัวอย่าง donation 100 pp → 1 claim หัก 100 → 78/11/7/4 pp
- ตัวอย่าง donation 500 pp → claim 1 หัก 270 → 210/30/20/10 · claim 2 หัก 230 → 179/26/17/8
- **ข้อดี:** เปลี่ยนน้อยสุด · big donation ยัง yield หลาย claim · small donation ก็ใช้ได้
- **ข้อเสีย:** ต้องคำนวณ rounding ทุก claim

### Option B — Fixed N claims per donation (เช่น N=4)
- ทุก donation แบ่งเป็น 4 claim เท่าๆ กัน `per_claim = donation / 4`
- 100 pp → 4 claims × 25 pp
- 10000 pp → 4 claims × 2500 pp
- **ข้อเสีย:** donation ก้อนใหญ่ = คนได้แต้มเยอะแต่จำนวน claimer จำกัดแค่ 4 คน · ก้อน 3 pp → 4 claims × 0.75 pp (เศษ)

### Option C — % ของ remaining ต่อ claim (เช่น 25%)
- แต่ละ claim = 25% ของ `remaining_points`
- 100 pp → claim 1 = 25, claim 2 = 18.75...
- **ข้อเสีย:** infinite tail, ต้องมี floor · rounding ยุ่ง

### Option D — Single-claim-per-donation
- Donation ถูกกดรับได้แค่ครั้งเดียว โดยคนเดียว
- 100 pp → 1 คนได้ 78/11/7/4 · 10000 pp → 1 คนได้ 7778/1111/741/370
- **ข้อดี:** เรียบง่ายสุด · **ข้อเสีย:** ก้อนใหญ่ผูกกับคนเดียวไม่กระจาย

**แนะนำ Option A** — เข้ากับ mental model เดิม, small donations ก็ใช้ได้, big donations ยังกระจายได้

---

## 2. รายละเอียด spec (Option A)

### 2.1 เศรษฐศาสตร์ที่ต้องล็อคใหม่ใน `config/economy.php`

**ลบออก:**
```
'academy_claim_cost' => 270,
'academy_claim_reward_claimer' => 210,
'academy_claim_reward_suggester' => 30,
'academy_claim_reward_school' => 20,
'academy_claim_reward_platform' => 10,
```

**เพิ่มใหม่:**
```php
'academy_claim_max_cost' => 270,             // ceiling ต่อ claim (backward-compatible)
'academy_claim_ratio_claimer' => 0.7778,     // 210/270
'academy_claim_ratio_suggester' => 0.1111,   // 30/270
'academy_claim_ratio_school' => 0.0741,      // 20/270
'academy_claim_ratio_platform' => 0.0370,    // 10/270
```

**คงเดิม (ไม่แตะ):**
```
'academy_claim_cap_per_donation_per_day' => 5,
'academy_claim_cap_total_per_day' => 20,
'platform_personal_code' => 99999999,
'donation_pp_per_baht' => 1080,
```

Sanity check: `0.7778 + 0.1111 + 0.0741 + 0.0370 = 1.0000` ✓

### 2.2 Migration: ไม่ต้องแก้ schema
ตาราง `academy_donate_claims` ยังใช้ได้เพราะ `amount_*` เป็น `unsignedInteger` — เก็บค่า absolute หลัง split ตามปกติ ไม่ต้องเก็บเปอร์เซ็นต์

Invariant check ต้องเปลี่ยน — ดู 2.4

### 2.3 Backend: `StorePointDonationRequest.php`
เอา `min:270` ออก, ปรับเป็น `min:1`:
```php
'points_amount' => 'required|integer|min:1|max:1000000',
```
ลบ custom message ที่บอก "ขั้นต่ำ 270 แต้ม" ออกด้วย

### 2.4 Backend: `AcademyDonateService::createPointDonation`
เอา guard `< 270` ออก คงไว้แค่ `< 1`:
```php
if ($pointsAmount < 1) {
    throw new DomainException('Donation must be at least 1 point.');
}
```

### 2.5 Backend: `AcademyClaimService::claimSpecific` (rewrite ส่วนคำนวณ)

**ก่อน (คงที่):**
```php
$cost = (int) config('economy.academy_claim_cost');  // 270
if ((int) $donation->remaining_points < $cost) {
    throw new DomainException('donation_not_claimable');
}
$rewardClaimer = (int) config('economy.academy_claim_reward_claimer');   // 210
// ... fixed values
```

**หลัง (แปรผัน):**
```php
$maxCost = (int) config('economy.academy_claim_max_cost');  // 270 ceiling
$remaining = (int) $donation->remaining_points;
if ($remaining < 1) {
    throw new DomainException('donation_not_claimable');
}
$cost = min($remaining, $maxCost);

$ratioClaimer = (float) config('economy.academy_claim_ratio_claimer');
$ratioSuggester = (float) config('economy.academy_claim_ratio_suggester');
$ratioSchool = (float) config('economy.academy_claim_ratio_school');
$ratioPlatform = (float) config('economy.academy_claim_ratio_platform');

// Compute in order: claimer, suggester (if any), school, platform = residual (กัน round loss)
$rewardClaimer = (int) round($cost * $ratioClaimer);
$rewardSuggester = $suggester ? (int) round($cost * $ratioSuggester) : 0;
$rewardSchoolBase = (int) round($cost * $ratioSchool);
$rewardSchool = $rewardSchoolBase + ($suggester ? 0 : (int) round($cost * $ratioSuggester));
$rewardPlatform = $cost - $rewardClaimer - $rewardSuggester - $rewardSchool;

// Guard against negative platform after rounding (rare edge cases)
if ($rewardPlatform < 0) {
    $rewardSchool += $rewardPlatform;   // absorb into school
    $rewardPlatform = 0;
}
```

**Invariant เดิม:** `$rewardClaimer + $rewardSuggester + $rewardSchool + $rewardPlatform === $cost` ✓
เดิมเทียบกับ 270 คงที่ — เปลี่ยนเป็นเทียบ `$cost` ที่แปรผัน (ซึ่งโค้ดหลัง 2b ทำอยู่แล้ว)

**Edge cases ที่ต้องเทส:**
| remaining | cost | claimer(78%) | suggester(11%) | school(7%) | platform(4%) | sum |
|---|---|---|---|---|---|---|
| 500 | 270 | 210 | 30 | 20 | 10 | 270 |
| 500 (no suggester) | 270 | 210 | 0 | 50 | 10 | 270 |
| 100 | 100 | 78 | 11 | 7 | 4 | 100 |
| 100 (no suggester) | 100 | 78 | 0 | 18 | 4 | 100 |
| 10 | 10 | 8 | 1 | 1 | 0 | 10 |
| 10 (no suggester) | 10 | 8 | 0 | 2 | 0 | 10 |
| 3 | 3 | 2 | 0 | 1 | 0 | 3 |
| 1 | 1 | 1 | 0 | 0 | 0 | 1 |

⚠️ ระวัง: donation 1 pp = claimer ได้ 1 อย่างเดียว platform/school ได้ 0 — school ก็ได้ 0 ทั้งที่ควรได้เศษ เพราะ round(1 × 0.0741) = 0 · โมเดลนี้ยอมได้ ไม่ใช่บั๊ก

### 2.6 Backend: `AcademyClaimController::claimable` (ปรับ preview)

Card ต้องรู้ว่า **claimer จะได้กี่ pp** เพื่อโชว์ปุ่ม เดิมเป็น config คงที่ 210 ตอนนี้ต้องคำนวณ per-donation

เพิ่ม field `claimer_reward_preview` ต่อ donation ใน response:
```php
'donations' => $donations->map(function ($d) use ($ratioClaimer, $maxCost) {
    $cost = min((int) $d->remaining_points, $maxCost);
    return [
        // ... fields เดิม
        'claim_amount_preview' => $cost,
        'claimer_reward_preview' => (int) round($cost * $ratioClaimer),
    ];
})
```

ลบ (ถ้ามี) การส่ง global `claimer_reward` จาก config

### 2.7 Frontend: `useAcademyPoints.ts`

เพิ่ม field ใหม่ใน `AcademyDonationClaimable` type:
```ts
export interface AcademyDonationClaimable {
  // ... เดิม
  claim_amount_preview: number
  claimer_reward_preview: number
}
```

### 2.8 Frontend: `AcademyDonorCard.vue`

**เปลี่ยน 2 จุด:**

1. ตัด prop `claimerReward` ออก — ใช้ `donation.claimer_reward_preview` แทน
```vue
<button ...>
  กดรับ {{ donation.claimer_reward_preview }} pp
</button>
```

2. **ลบ disabled reason "แต้มในกองนี้ไม่พอ"** ออกเพราะไม่มี concept นี้แล้ว
```ts
const disabledReason = computed(() => {
  if (props.capReached) return 'ครบเพดาน 20 ครั้ง/วัน แล้ว'
  if (props.donation.my_claims_today >= 5) return 'กดรับจากรายการนี้ครบ 5 ครั้งวันนี้แล้ว'
  if (props.donation.remaining_points < 1) return 'กองนี้หมดแล้ว'
  return ''
})
```

3. Subtitle "คงเหลือ / xxx" ยังใช้ได้ (แสดง original donation size)

### 2.9 Frontend: `AcademyClaimWidget.vue`

ตัด prop `:claimer-reward="210"` ที่ส่งให้แต่ละ card ออก:
```vue
<AcademyDonorCard v-for="donation in items" :key="donation.id" :donation="donation" :academy-id="academyId" :cap-reached="meta.cap_reached" @claimed="claim" />
```

### 2.10 Frontend: `SupportDonationModal.vue`

**Revert min 270:**
```vue
<input :min="1" ... />
<p v-if="scope === 'academy'" ...>บริจาคเท่าไหร่ก็ได้ กดรับแบ่งสัดส่วนอัตโนมัติ</p>
<button :disabled="type === 'point' && points < 1" ...>
```

**pointPresets** ปรับ:
```ts
const pointPresets = computed(() => props.scope === 'academy' ? [50, 100, 300, 500, 1000, 2500] : [50, 100, 300, 500, 1000, 2500])
```
(กลับไปเหมือน scope อื่น — สามารถ merge เป็นค่าคงที่ได้แล้ว)

**Default points:**
```ts
const points = ref(props.scope === 'academy' ? 100 : 100)  // เท่ากันได้แล้ว
```

---

## 3. ไฟล์ที่ต้องแก้ (checklist)

Backend (5):
- [ ] `api/nuxnanravel/config/economy.php` — swap keys per section 2.1
- [ ] `api/nuxnanravel/app/Http/Requests/AcademyDonate/StorePointDonationRequest.php` — `min:1`, ลบ custom message
- [ ] `api/nuxnanravel/app/Services/AcademyDonateService.php` — guard `< 1`
- [ ] `api/nuxnanravel/app/Services/AcademyClaimService.php` — rewrite reward math (section 2.5) + `listClaimable` preview (section 2.6)
- [ ] `api/nuxnanravel/app/Http/Controllers/Api/Academies/AcademyClaimController.php` — ลบ `delta` hardcoded, ให้ derive จาก response claim

Frontend (3):
- [ ] `ui/composables/useAcademyPoints.ts` — เพิ่ม 2 field ใน type
- [ ] `ui/components/academy/points/AcademyDonorCard.vue` — ใช้ `donation.claimer_reward_preview`, ตัด prop `claimerReward`, ลบ "แต้มในกองนี้ไม่พอ"
- [ ] `ui/components/academy/points/AcademyClaimWidget.vue` — ตัด `:claimer-reward` prop
- [ ] `ui/components/donation/SupportDonationModal.vue` — revert min 270 → 1, ปรับ hint + preset + default

Migration: **ไม่ต้อง**

Test:
- [ ] Manual QA per edge-case table ใน section 2.5
- [ ] Regression: donation 500 pp → claim 1 ต้องได้ 210/30/20/10 (เท่าเดิม เพราะ 500 >= 270)
- [ ] New: donation 100 pp → กดรับได้ → claimer ได้ ~78, invariant sum=100
- [ ] Cross-tier cap 20/day ยังทำงาน (ไม่แตะ logic)

---

## 4. Impact & rollback

**ผลกระทบต่อ donation ที่ค้างอยู่:**
- Donation 100 pp ของคุณตอนนี้ (`academy_id=1`) — หลัง deploy จะ **กดรับได้ทันที** (claim ครั้งเดียวหมด 100 pp)
- ไม่มี data migration จำเป็น เพราะ schema เดิมรองรับได้

**Rollback ถ้าโมเดลใหม่มีปัญหา:**
1. Revert 5 backend files + 3 frontend files ผ่าน git
2. `config/economy.php` restore keys เดิม
3. ไม่ต้อง roll back migration/DB (schema ไม่แตะ)
4. Donation ที่มี `remaining_points > 0` แต่ < 270 จะกลับไปเป็นสถานะ "unclaimable" อีก

**หมายเหตุ margin:**
- Public tier ยังคง 220/30/20 (ไม่แตะ config public — [[owner-can-self-donate]] · [[public-tier-monetization]])
- Academy margin ยังเท่าเดิม 3.70% claim + ~10% currency spread เพราะสัดส่วนไม่เปลี่ยน

---

## 5. Memory ที่ต้อง update ตอน execute

หลังทำเสร็จ (ไม่ใช่ตอนนี้):
- Update [[academy-direct-claim]] — เปลี่ยน "270 pp cost fixed" → "up to 270 pp, percentage split"
- ไม่แตะ [[public-tier-monetization]] (public tier ไม่กระทบ)
- อัปเดต `.agents/plans/academy-direct-claim.md` section 0 economics — swap ตารางเป็น percentage

---

## 6. Open question (ก่อนลงมือ ยืนยัน 2 ข้อ)

1. **Option A** (คงเดิม + drain last bit) หรือเอาแบบอื่น?
2. **สัดส่วนเดิม 77.78/11.11/7.41/3.70%** หรืออยากปรับ (เช่น ลด platform → 0 แล้วเอาไปให้ school)?

ถ้าตอบ (1)=A (2)=คงเดิม → แผนนี้ execute ได้เลยตามลำดับ 2.1 → 2.10

---

## 7. TL;DR — ลำดับ execute แนะนำ

1. แก้ [config/economy.php](../../api/nuxnanravel/config/economy.php) (section 2.1) — 30 วิ
2. แก้ [StorePointDonationRequest.php](../../api/nuxnanravel/app/Http/Requests/AcademyDonate/StorePointDonationRequest.php) — 1 นาที
3. แก้ [AcademyDonateService.php](../../api/nuxnanravel/app/Services/AcademyDonateService.php) — 1 นาที
4. แก้ [AcademyClaimService.php](../../api/nuxnanravel/app/Services/AcademyClaimService.php) (section 2.5 + 2.6) — 5–10 นาที (โค้ดยาวสุด)
5. แก้ [SupportDonationModal.vue](../../ui/components/donation/SupportDonationModal.vue) — 2 นาที
6. แก้ [useAcademyPoints.ts](../../ui/composables/useAcademyPoints.ts) — 30 วิ
7. แก้ [AcademyDonorCard.vue](../../ui/components/academy/points/AcademyDonorCard.vue) — 1 นาที
8. แก้ [AcademyClaimWidget.vue](../../ui/components/academy/points/AcademyClaimWidget.vue) — 30 วิ
9. Manual QA — donate 100 pp, กดรับ 1 ครั้ง, verify claimer ได้ ~78 pp
10. Update memory + plan doc

รวม ~15–20 นาที
