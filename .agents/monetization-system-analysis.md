# บทวิเคราะห์ระบบสร้างรายได้ (Monetization) — สถานะจริง ณ 2026-07-24

> อ่านจากซอร์สจริงทั้ง `api/nuxnanravel/` และ `ui/` (ไม่ใช่จากเอกสารแผนเก่า)
> จุดประสงค์: ให้ทุกคนเข้าใจตรงกันว่า "ตอนนี้ระบบเป็นยังไงจริง ๆ" ก่อนต่อยอด

---

## 0. สรุปผู้บริหาร (อ่านแค่นี้ก็พอเข้าใจภาพรวม)

ภาพที่ผู้ใช้เข้าใจ (บริจาค + ลงโฆษณา) **ถูกต้อง แต่ยังไม่ครบ** — ของจริงมี **3 มิติ**ซ้อนกัน:

1. **2 วิธีหาเงิน** — บริจาค (donation/support) และ โฆษณา (advertisement)
2. **3 ระดับปลายทาง** — แพลตฟอร์ม (platform/public) · โรงเรียน (academy) · รายวิชา (course)
3. **2 รุ่นของโค้ด (legacy vs ใหม่) ที่ยังทำงานคู่กันอยู่ทั้งคู่** ← นี่คือปัญหาใหญ่ที่สุด

ผลคือตอนนี้มี **6 ระบบย่อยที่ทำงานพร้อมกัน** และมี **สูตรเศรษฐศาสตร์ที่ไม่ตรงกันอย่างน้อย 4 ชุด**
ถ้าไม่ล็อค SSOT ก่อน การพัฒนาต่อจากนี้จะยิ่งแตกออกไปอีก

| # | ระบบย่อย | ตาราง/โมเดลหลัก | รุ่น | สถานะ |
|---|---|---|---|---|
| D1 | บริจาคเข้าแพลตฟอร์ม (กดรับแต้ม) | `donates`, `donate_recipients` | legacy | ใช้งานจริง |
| D2 | บริจาคเข้ารายวิชา | `course_donates` | ใหม่ | ใช้งานจริง |
| D3 | บริจาคเข้าโรงเรียน | `academy_donates` | ใหม่ | ใช้งานจริง |
| A1 | โฆษณา — เส้นทาง legacy (จ่าย pp ดูโฆษณา) | `adverts`, `advert_viewers` | legacy | **ยังใช้งานจริง** ผ่าน `CampaignWidget` |
| A2 | โฆษณา — เส้นทางใหม่ (delivery pipeline + revenue share) | `adverts`, `campaign_delivery_events` | ใหม่ | ใช้งานจริง ผ่าน `AdViewerModal` |
| C1 | กองทุนแต้มรายวิชา + แคมเปญกดรับแต้ม | `course_point_*` | ใหม่ | ใช้งานจริง (งานล่าสุด) |

---

## 1. หน่วยเงินและอัตราแลกเปลี่ยนที่ระบบใช้อยู่จริง

ระบบมี **3 หน่วย** ที่ไหลเวียน และนี่คือรากของความสับสนเกือบทั้งหมด:

| หน่วย | เก็บที่ | ความหมาย |
|---|---|---|
| **THB (บาท)** | `users.wallet`, `wallet_transactions` | เงินจริง ถอนออกธนาคารได้ |
| **pp (แต้มส่วนตัว)** | `users.pp`, `points_transactions` | แต้มของผู้ใช้ |
| **แต้มกองทุน** | `course_point_accounts.balance`, `academy_point_accounts.balance` | แต้มของรายวิชา/โรงเรียน |

### อัตราที่ประกาศไว้ในโค้ด

| อัตรา | ค่า | ที่มา |
|---|---|---|
| pp ↔ THB (official) | **1,200 pp = 1 THB** | `PointsService::convertPointsToWallet` (hardcode), `WalletService::convertWalletToPoints` (hardcode) |
| บริจาค → แต้มที่แจก | **1,080 pp / 1 THB** | `Donate::getTotalPointsAttribute` (hardcode `* 1080`), `config('campaign.points_rate.*')` |
| ราคาโฆษณา | **0.10 THB / (view × วินาที)** | `config('campaign.ad_price_per_view_second')` |
| รางวัลรวมต่อการดู 1 ครั้ง (เส้นทางใหม่) | **20 pp / วินาที** | `config('campaign.gross_reward_per_view_per_second')` |
| รางวัลผู้ชม (เส้นทาง legacy) | **0.06 THB/วินาที + pp ที่จ่ายไป/1200** | `config('campaign.viewer_reward_per_second')` |
| รางวัลผู้แนะนำ (legacy) | **0.02 THB/วินาที** | `config('campaign.referrer_reward_per_second')` |
| ส่วนแบ่งโฆษณา (เส้นทางใหม่) | student 60 / course 25 / academy 10 / platform 5 | seed migration `2026_07_18_220000` |
| ส่วนแบ่ง support (เส้นทาง campaign) | academy 70 / instructor 20 / platform 10 | `config('campaign.revenue_split')` |
| ถอนแต้มรายวิชาขั้นต่ำ | 24,000 pp (= 20 THB) | `CoursePointAccount::MINIMUM_WITHDRAWAL` |
| ถอนเงินขั้นต่ำ / ค่าธรรมเนียม | 25 THB / 1% ขั้นต่ำ 5 THB | `config/wallet.php` |

> **ข้อสังเกต:** ส่วนต่าง 1,200 vs 1,080 = **มาร์จิ้นแพลตฟอร์ม 10%** ของทุกการบริจาค — เป็นดีไซน์ที่สมเหตุสมผล แต่ **ไม่มีที่ไหนเขียนไว้เป็นเอกสาร** และเลข 1080/1200 ถูก hardcode กระจายหลายจุด (ทั้ง config, model accessor, และตัวเลขดิบใน controller)

---

## 2. เส้นทางที่ 1 — การสนับสนุนจากผู้บริจาค

### 2.1 D1 — บริจาคเข้าแพลตฟอร์ม (ระบบเดิม ที่ผู้ใช้อธิบายไว้)

**ไฟล์:** `app/Http/Controllers/Api/Earn/DonateController.php`, `app/Models/Donate.php`
**Route:** `routes/earn/donate.php` (บรรทัด 63–87)

```
ผู้บริจาค (สมาชิกหรือไม่ก็ได้)
  → POST /api/supports/donates   ← public, ไม่ต้อง login
     payment_method: slip | wallet | points
  → status = 0 (pending) ถ้าเป็น slip
  → remaining_points = amounts × 1080
  ↓
แอดมิน PATCH /api/plearnd-admin/supports/donates/{id}/receive
  → status = 1 (approved)
  ↓
สมาชิกอื่น GET /api/donates/{id}/get-donate
  → หัก remaining_points 270
  → ผู้กดรับได้ +240 pp
  → ผู้แนะนำ (suggester_code) ได้ +30 pp
  → จำกัด 10 ครั้ง/คน/วัน/การบริจาค 1 รายการ
```

**ตรงกับที่ผู้ใช้อธิบาย 100%** และรองรับ guest จริง (anonymous → `donor_name = 'ไม่ประสงค์ออกนาม'` และบังคับแนบสลิป)

**สิ่งที่ควรรู้เพิ่ม:**
- `payment_method = wallet` และ `points` **ข้ามการอนุมัติของแอดมินทันที** (`status = 1` เลย) — สมเหตุสมผลเพราะเงินอยู่ในระบบแล้ว แต่ `points` ใช้อัตรา **1 THB = 100 pp** (`DonateController.php:199`) ซึ่ง **ไม่ตรงกับ 1,200 pp/THB ที่ใช้ทุกที่** → ผู้ใช้บริจาค 100 pp แล้วได้เครดิตเสมือน 1 THB = 1,080 pp แจกออกไป (ขยายแต้ม 10 เท่า)
- ไม่มี transaction/lock ในเส้นทาง `get-donate` — race condition ทำให้แจกเกินได้
- `allAvailableDonates` และ `widget` แสดงรายการ **status 0 (pending) ด้วย** → ผู้ใช้เห็นรายการที่ยังไม่อนุมัติปนอยู่

### 2.2 D2 — บริจาคเข้ารายวิชา (ระบบใหม่)

**ไฟล์:** `app/Services/CourseDonateService.php`, `app/Services/PointLedgerService.php`

2 แบบ:
- **บริจาคแต้ม** (`POST /courses/{c}/donations/points`) — หัก pp ผู้บริจาค → เครดิตเข้า `course_point_accounts` **1:1** ทันที (status `completed`) ไม่ต้องรออนุมัติ
- **บริจาคเงินสด** (`POST /courses/{c}/donations/cash`) — แนบสลิป → status `pending` → แอดมินแพลตฟอร์มอนุมัติ → เครดิตเข้ากองทุนรายวิชา

มี guard ครบ: `donation_enabled` ต่อรายวิชา, idempotency key, lock, เจ้าของวิชาอนุมัติเงินบริจาคของตัวเองไม่ได้

### 2.3 D3 — บริจาคเข้าโรงเรียน

โครงเดียวกับ D2 (`AcademyDonateService`) + เพิ่ม guard "บริจาคให้โรงเรียนตัวเองไม่ได้"
มี **AcademyAllocationService** ให้ผู้ดูแลโรงเรียนโอนแต้มจากกองทุนโรงเรียน → กองทุนรายวิชาในสังกัด

---

## 3. เส้นทางที่ 2 — การลงโฆษณา

### 3.1 การสร้างแคมเปญ (เส้นทางใหม่ — เป็นทางหลักแล้ว)

**ไฟล์:** `app/Http/Controllers/Api/Campaign/CampaignController.php`, `StoreCampaignRequest.php`
**ตาราง:** `adverts` (ชื่อตารางเดิม แต่ถูกยกระดับเป็น "campaign" ด้วย migration `2026_07_12_120000`)

โครงข้อมูลปัจจุบัน:
- `campaign_type`: `advertisement` | `support` ← **ทั้งโฆษณาและการสนับสนุน ใช้ตารางเดียวกัน**
- `scope_type`: `public` | `academy` | `course` ← ระดับที่จะแสดง
- `payment_status`: `unpaid` → `pending_slip` → `paid` → `refunded`
- `review_status`: `pending` → `approved` | `rejected`

**การคิดราคา** (บังคับตรวจฝั่ง server แล้ว — ดี):
```
budget_amount = total_views × duration × 0.10 THB
duration ∈ {5,10,15,30,60} วินาที · total_views ∈ [100, 100000]
```
เช่น 1,000 views × 10 วินาที = **1,000 บาท**

**การชำระเงิน:** `wallet` (หักทันที) หรือ `slip` (แนบสลิป → รอแอดมิน)
**การอนุมัติ:** `PATCH /campaigns/{id}/review` — มี state machine ป้องกันอนุมัติ/refund ซ้ำ (`CampaignController.php:192–204`) ✅

### 3.2 การดูโฆษณาและรับรางวัล — **มี 2 เส้นทางที่ทำงานพร้อมกัน**

นี่คือประเด็นสำคัญที่สุดของเอกสารนี้

#### เส้นทาง A2 (ใหม่, ถูกต้องตามเจตนา)
`AdDeliveryService` + `RewardDistributionService`
```
POST /adverts/{id}/deliveries/start      → ออก delivery token (HMAC)
POST /ad-deliveries/{id}/heartbeat       → ส่ง visibility ratio ทุก ~5 วิ
POST /ad-deliveries/{id}/complete        → ตรวจแล้วจ่ายรางวัล
```
- ตรวจเวลาที่ดูจริง (`watched >= required_duration - 2`)
- ตรวจ visibility ratio ≥ 0.7
- กัน replay (สถานะ `replayed` → 409)
- rate limit + จำกัด 5 การดู/คน/วัน (ทั้งระบบ)
- รางวัล gross = `duration × 20 pp` แล้วแบ่งตาม `RevenueSharePolicy` (60/25/10/5)
- นักเรียนได้ **pp**, กองทุนรายวิชา/โรงเรียนได้ **แต้มกองทุน**, ส่วนแพลตฟอร์มบันทึกที่ `platform_earned`
- **ใช้จริงจาก:** `AdViewerModal.vue` → หน้า `/Earn/Advertise` และ modal กดรับแต้มของรายวิชา

#### เส้นทาง A1 (legacy, ยังเปิดอยู่และยังถูกเรียกจริง)
`CampaignViewService::rewardedView` (`POST /campaigns/{id}/view`)
- **ผู้ชมต้องจ่าย pp** = `duration × 20` เพื่อดูโฆษณา (!)
- แล้วได้ **เงินบาทเข้า wallet** = `duration × 0.06 + pp_ที่จ่าย/1200`
- ผู้แนะนำได้ `duration × 0.02` บาท (ถ้าไม่มีผู้แนะนำ → ตกไปที่บัญชีแพลตฟอร์ม `personal_code = 99999999`)
- **ไม่มี** การตรวจ visibility, ไม่มี heartbeat, ไม่มี revenue share policy, ไม่บันทึก `CampaignDeliveryEvent` แบบเต็ม
- **ใช้จริงจาก:** `ui/components/campaign/CampaignWidget.vue:102` ซึ่งถูกฝังไว้ **5 จุด**:
  `AdvertisesWidget` (sidebar สาธารณะ), `CoursePageShell` (sidebar รายวิชา), `academies/[name].vue`, `Learn/Courses/[id]/support.vue`

และยังมี **เส้นทางที่ 3** ที่เหลือค้าง: `AdvertController::view` (`POST /advertises/{id}/view`, `AdvertController.php:191`) — ตรรกะเดียวกับ A1 แต่คนละโค้ด ไม่มี frontend เรียกแล้ว แต่ route ยังเปิด

**ผลกระทบเชิงเศรษฐศาสตร์ (โฆษณา 10 วินาที ที่ผู้ลงโฆษณาจ่ายมา 1 บาท):**

| เส้นทาง | ผู้ชมจ่าย | ผู้ชมได้ | ผู้แนะนำได้ | รายวิชา/โรงเรียนได้ | แพลตฟอร์มเหลือ |
|---|---|---|---|---|---|
| A2 (ใหม่) | 0 | 120 pp (0.10฿) | 0 | 50 pp + 20 pp | 10 pp (~0.83฿ เหลือที่แพลตฟอร์ม) |
| A1 (legacy) | 200 pp | 0.767 ฿ | 0.20 ฿ | 0 | **0.033 ฿** |

→ เส้นทาง legacy จ่ายออกเกือบ 97% ของค่าโฆษณา และยังบังคับให้ผู้ชม**เผาแต้มตัวเอง**เพื่อดูโฆษณา ซึ่งขัดกับเจตนาที่ผู้ใช้อธิบาย ("สมาชิกมากดดูโฆษณา **และรับส่วนแบ่งรายได้**")

---

## 4. จุดบรรจบ — แคมเปญกดรับแต้มของรายวิชา (งานล่าสุด)

**ไฟล์:** `CoursePointCampaignController::view` + `CoursePointAccountService::claimManualCampaign`
**UI:** `ui/components/learn/course/points/CampaignClaimModal.vue`

นี่คือจุดที่ทั้ง 2 เส้นทางมาเจอกัน — modal 3 โหมด:

| โหมด | เงื่อนไข | สิ่งที่ผู้เรียนเห็น | รางวัล |
|---|---|---|---|
| `donor` | มี `course_donates` แบบ point ที่ `remaining_points > 0` | การ์ดผู้บริจาค + นับถอยหลัง 10 วิ | แต้มจากแคมเปญ |
| `ad` | ไม่มีผู้บริจาค แต่มีโฆษณา scope=course | ฝัง AdViewer + delivery pipeline เต็ม | รางวัลโฆษณา **+** แต้มจากแคมเปญ |
| `empty` | ไม่มีทั้งคู่ | โลโก้วิชา + ข้อความขอบคุณ + 10 วิ | แต้มจากแคมเปญ |

ที่มาของแต้มที่แจกคือ **กองทุนรายวิชา** (`course_point_accounts.balance`) เสมอ — ไม่ว่าจะโหมดไหน
`viewed_donor_id` / `viewed_donation_id` / `viewed_ad_id` บันทึกไว้เพื่อ audit ว่าผู้เรียนดูอะไรตอนกดรับ

---

## 5. ทางออกของเงิน (Payout)

```
กองทุนรายวิชา ──(CoursePointWithdrawalRequest)──▶ pp ของเจ้าของวิชา ──(convert 1200:1)──▶ wallet ──(WalletController::withdraw)──▶ ธนาคาร
     ▲                                                                                             (ขั้นต่ำ 25฿, ค่าธรรมเนียม 1% ขั้นต่ำ 5฿, maker-checker ≥10,000฿)
     │
กองทุนโรงเรียน ──(AcademyAllocationService)──┘   ◀── ไม่มีทางออกเป็นเงินโดยตรง
```

- **ถอนแต้มรายวิชา** มี state machine เต็ม: pending → reviewing → approved → paid (+ rejected/cancelled) พร้อม reserve/release, maker-checker (threshold `config('wallet.course_withdraw.maker_checker_threshold')` = 5,000 โดย default **แต่ไม่มีคีย์นี้ใน `config/wallet.php` จริง**), แนบหลักฐานการโอน
- **`markPaid` จ่ายออกเป็น pp** (`CoursePointWithdrawalService.php:110`) ไม่ใช่เงินบาท — แต่ endpoint บังคับ `payment_reference` + สลิปการโอน ซึ่งสื่อว่าเป็นการโอนเงินจริง → **ความหมายกำกวม ต้องตัดสินใจ**
- **กองทุนโรงเรียนถอนเป็นเงินไม่ได้เลย** — `AcademyPointAccountService` มีแต่ `credit*` และการ allocate ออกไปยังรายวิชา ไม่มี withdrawal path

---

## 6. หน้าจอฝั่งแอดมิน (ครบกว่าที่คิด แต่กระจัดกระจาย)

| งาน | หน้า | เรียก API |
|---|---|---|
| อนุมัติบริจาคแพลตฟอร์ม (D1) | `pages/nuxnan-admin/supports/index.vue` | `/plearnd-admin/supports/donates` |
| อนุมัติแคมเปญ/โฆษณา | `pages/PlearndAdmin/Support/ApproveAdvertise.vue` | `/campaigns/admin`, `/campaigns/{id}/review` |
| อนุมัติบริจาครายวิชา | `pages/PlearndAdmin/course-donations.vue` | `/plearnd-admin/course-donations` |
| อนุมัติบริจาคโรงเรียน | `pages/PlearndAdmin/academy-donations.vue` | `/plearnd-admin/academy-donations` |
| อนุมัติถอนแต้มรายวิชา | `pages/PlearndAdmin/course-withdrawals.vue` | `/plearnd-admin/course-withdrawals` |
| นโยบายส่วนแบ่งรายได้ | `pages/PlearndAdmin/revenue-share-policies.vue` | `/plearnd-admin/revenue-share-policies` |
| Risk events | `pages/PlearndAdmin/risk-events.vue` | `/plearnd-admin/risk-events` |
| อนุมัติถอน/เติมเงิน wallet | `pages/nuxnan-admin/wallet/pending.vue` | `/nuxnan-admin/...` |

⚠️ ใช้ **2 prefix ปนกัน**: `pages/PlearndAdmin/` และ `pages/nuxnan-admin/` → แอดมินต้องจำว่างานไหนอยู่โฟลเดอร์ไหน และไม่มี dashboard รวม "งานค้างอนุมัติทั้งหมด"

---

## 7. ระบบตรวจจับทุจริต (มีอยู่ แต่ไม่ทำงานอัตโนมัติ)

`FraudDetectionService` มี 5 กฎ: donation velocity, self-donation cluster, ad fraud, academy negative balance, ad revenue policy mismatch
→ สร้าง `RiskEvent` ให้แอดมินตามดู

**แต่:** คำสั่ง `risk:scan` **ไม่ได้ถูกใส่ใน `routes/console.php`** (มีแต่ `reconcile:all --emit-risk`, `wallet:reconcile`, `course-points:cleanup-reservations`)
→ กฎเหล่านี้จะไม่ทำงานเลยจนกว่าจะมีคนรันมือ

---

## 8. ปัญหาที่พบ — เรียงตามความรุนแรง

### 🔴 P0 — กระทบเงิน/แต้มโดยตรง

| # | ปัญหา | ที่ตั้ง |
|---|---|---|
| 1 | **โหมด `donor` จะตายเงียบ** — `createPointDonation` ไม่เซ็ต `remaining_points` (default 0) ขณะที่ migration backfill แค่ครั้งเดียว → การบริจาคแต้มใหม่ทุกรายการมี `remaining_points = 0` → `view` endpoint ที่กรอง `remaining_points > 0` จะไม่เจอ → modal ตกไปโหมด ad/empty เสมอ | `CourseDonateService.php:35` + migration `2026_07_23_180000:21-24` |
| 2 | **บริจาคเงินสดแปลงเป็นแต้มผิดหน่วย** — `(int) $d->cash_amount` เครดิตเป็นแต้ม 1:1 → บริจาค 1,000 บาท ได้แต้มเข้ากองทุน 1,000 แต้ม (มูลค่า 0.83 บาท) แทนที่จะเป็น ~1,080,000 แต้ม | `CourseDonateService.php:75`, `AcademyDonateService.php:75` |
| 3 | **หัก budget ผิดหน่วย** — `$advert->decrement('budget_amount', $gross)` หักงบ (บาท) ด้วยจำนวนแต้ม → โฆษณา 10 วิ งบ 1,000 บาท จะ "งบหมด" หลังถูกดูแค่ 5 ครั้ง | `RewardDistributionService.php:42-46` |
| 4 | **แคมเปญ `support` จ่ายซ้ำสองทาง** — ให้ pp แก่ผู้สนับสนุนเท่ากับ `budget × 1080` **และ** แจกเงินสด 70/20/10 ให้โรงเรียน/ผู้สอน/แพลตฟอร์มพร้อมกัน → จ่ายออก ~190% ของเงินที่รับมา | `SupportPaymentService.php:68-73` + `:130-138` |
| 5 | **2 เส้นทางการดูโฆษณาที่เศรษฐศาสตร์ขัดกัน** (ดู §3.2) — และเส้นทางที่ผู้ใช้เห็นบ่อยที่สุด (sidebar widget) คือเส้นทาง legacy ที่บังคับผู้ชมจ่าย pp | `CampaignViewService.php:34-77` vs `AdDeliveryService.php` |
| 6 | **บริจาคด้วย points ใช้อัตรา 1 THB = 100 pp** ต่างจาก 1,200 ที่ใช้ทุกที่ → ขยายแต้มในระบบ ~10 เท่าต่อรายการ | `DonateController.php:199` |

### 🟠 P1 — ความสอดคล้อง / ความปลอดภัย

| # | ปัญหา | ที่ตั้ง |
|---|---|---|
| 7 | `AdvertController::approve` ตั้งแค่ `status = 1` ไม่แตะ `review_status` → ข้าม state machine ทั้งชุด และไม่ตรวจการชำระเงิน | `AdvertController.php:279` |
| 8 | `CampaignDeliveryService::query` ไม่กรอง `payment_status = paid` → แคมเปญที่ถูก refund แต่ `review_status` ยัง approved จะยังถูกส่งออกอยู่ | `CampaignDeliveryService.php:14-24` |
| 9 | `get-donate` (D1) ไม่มี transaction/lock → แจกแต้มเกินได้ในกรณี concurrent | `DonateController.php:310-350` |
| 10 | `risk:scan` ไม่ได้ถูก schedule | `routes/console.php` |
| 11 | `config('wallet.course_withdraw.*')` ถูกอ้างแต่ไม่มีในไฟล์ config → threshold maker-checker ใช้ค่า default เงียบ ๆ | `CoursePointWithdrawalService.php:64,140` |
| 12 | `CoursePointCampaignController::view` ไม่มี rate limit และเปิด donor PII (ชื่อ/username/personal_code/profile) ให้สมาชิกทุกคนที่กดดู | `CoursePointCampaignController.php:63-100` |
| 13 | `authorizeCourseAdmin` ใน CoursePointCampaignController เช็คแค่ `course->user_id` หรือ role `admin` — ไม่ใช้ `Course::isAdmin()` เหมือนที่อื่น → co-admin ของวิชาสร้างแคมเปญไม่ได้ | `CoursePointCampaignController.php:131-138` |
| 14 | `remaining_points` ของ `course_donates` ถูกหักตอน claim แต่ **ไม่ผูกกับ ledger จริง** (แต้มที่แจกมาจาก account balance) → เป็นบัญชีคู่ขนานที่ drift ได้ | `CoursePointAccountService.php:432-437` |

### 🟡 P2 — ประสบการณ์ใช้งาน / โครงสร้าง

- แสดงรายการบริจาคสถานะ pending ปนกับ approved ในหน้า public (`allAvailableDonates`, `widget`)
- `pages/PlearndAdmin/` vs `pages/nuxnan-admin/` ปนกัน ไม่มี dashboard รวมงานค้าง
- `AdvertResource` vs `CampaignResource` — 2 shape ของ entity เดียวกัน
- `Advert` model ใช้ `$guarded = []` ทั้งที่ถือฟิลด์การเงิน
- ไม่มีเอกสารสาธารณะ/หน้าอธิบายอัตราแลกเปลี่ยนให้ผู้บริจาคและผู้ลงโฆษณาเห็นก่อนจ่ายเงิน

---

## 9. ช่องว่างเทียบกับเจตนาที่ผู้ใช้อธิบาย

| ผู้ใช้อธิบายว่า | ของจริง |
|---|---|
| ผู้บริจาค "อาจเป็นสมาชิกหรือไม่ก็ได้" | ✅ ทำได้เฉพาะ **D1 (แพลตฟอร์ม)** — D2/D3 (รายวิชา/โรงเรียน) **บังคับ login + verified** |
| ผู้ลงโฆษณา "อาจเป็นสมาชิกหรือไม่ก็ได้" | ❌ **ยังทำไม่ได้เลย** — ทุก route ของ campaign อยู่หลัง `auth:api` + `verified` (ตรงกับ memory: guest advertiser ยัง paused) |
| แอดมินตรวจยอดโอน แล้วอนุมัติ | ✅ มีครบทุกช่องทาง แต่ **แยก 5 คิว 2 prefix** ไม่มีศูนย์รวม |
| สมาชิก "รับส่วนแบ่งรายได้ตามอัตราส่วนของตัวเอง" | ⚠️ มี `RevenueSharePolicy` (scope: campaign > course > academy > platform) แต่ **เฉพาะเส้นทางใหม่** — เส้นทาง legacy ที่ยัง active ใช้สูตร hardcode คนละชุด |
| โฆษณาแสดง "ตามเงื่อนไขที่กำหนดไว้" | ✅ มี scope/inherit/active_from/until/remaining_views — ครบและออกแบบดี |

---

## 10. ข้อเสนอทิศทาง — สิ่งที่ต้องล็อคก่อนเขียนโค้ดเพิ่ม

### D1 — ล็อค "หน่วยเงินและอัตรา" เป็น SSOT เดียว
สร้าง `config/economy.php` (หรือขยาย `config/campaign.php`) แล้ว **ห้าม hardcode 1080/1200/270/240/30/100 ที่ไหนอีก**
พร้อมประกาศชัดว่า: **มูลค่าอ้างอิงคือ 1,200 pp = 1 THB · แพลตฟอร์มกินมาร์จิ้น 10% ที่จุดแปลงเงิน→แต้ม**

### D2 — เลือกเส้นทางโฆษณาเส้นเดียว
แนะนำ: **คงเส้นทาง A2 (delivery pipeline) เป็นทางเดียว** แล้ว
1. เปลี่ยน `CampaignWidget.vue` ให้เรียก delivery pipeline (หรือเปิด `AdViewerModal`) แทน `POST /campaigns/{id}/view`
2. ปลด route `POST /campaigns/{id}/view` และ `POST /advertises/{id}/view` ออก (ทำเป็น 410 Gone ก่อนสัก 1 รอบ deploy)
3. ลบ `AdvertController::view/approve/reject` และย้าย `index/widget/more` ไปกรองด้วย `review_status` แทน `status`

### D3 — นิยาม "การถอน" ของกองทุนรายวิชาให้ชัด
ต้องตอบว่า `markPaid` = **จ่ายเป็น pp** (แล้วเจ้าของค่อยไปแปลงเป็นเงินเอง) หรือ **จ่ายเป็นเงินโอนเข้าบัญชี**
ตอนนี้โค้ดทำอย่างแรกแต่ UX/หลักฐานสื่ออย่างหลัง — ถ้าเลือกอย่างแรก ควรตัดการบังคับแนบสลิปโอนออก

### D4 — ตัดสินใจเรื่อง `campaign_type = support`
ทับซ้อนกับ D2/D3 (บริจาครายวิชา/โรงเรียน) เกือบทั้งหมด และมีบั๊กจ่ายซ้ำ (P0 #4)
แนะนำ: **ยุบ `support` ทิ้ง** ให้ `adverts` เหลือแค่โฆษณาอย่างเดียว แล้วให้การสนับสนุนทั้งหมดไหลผ่าน `course_donates`/`academy_donates`

### D5 — กองทุนโรงเรียนถอนเป็นเงินได้หรือไม่
ถ้าได้ → ต้องทำ `AcademyPointWithdrawalRequest` ขนานกับของรายวิชา
ถ้าไม่ได้ → ต้องบอกในหน้า UI ให้ชัดว่าแต้มโรงเรียน "ใช้จัดสรรลงรายวิชาเท่านั้น"

### D6 — guest advertiser (ตามที่ผู้ใช้อธิบาย)
ต้องออกแบบ: ตัวตนผู้ลงโฆษณาที่ไม่มี user (email/phone + slip), การแจ้งผลอนุมัติ, `beneficiary_id` กรณีไม่มี user, และ rate limit/anti-abuse ของ endpoint สาธารณะที่รับไฟล์อัปโหลด

### ลำดับงานที่แนะนำ
1. **แก้ P0 #1, #2, #3** ก่อน (บั๊กหน่วย — เงียบ กระทบข้อมูลจริงทุกวัน) + เขียน test ล็อคหน่วย
2. ล็อค D1 (SSOT อัตรา) แล้วแก้ P0 #6
3. ตัดสินใจ D2 แล้วปิดเส้นทาง legacy (แก้ P0 #5, P1 #7, #8)
4. ตัดสินใจ D4 (แก้ P0 #4)
5. schedule `risk:scan` + เติม `config/wallet.php` (P1 #10, #11)
6. รวมหน้าแอดมินเป็น dashboard เดียว (P2)
7. ค่อยเริ่ม D5/D6 (ฟีเจอร์ใหม่)
