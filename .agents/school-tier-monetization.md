# ระบบสร้างรายได้ระดับโรงเรียน/รายวิชา (School Tier) — เอกสารสโคป

> ต้นแบบ: [`public-tier-monetization.md`](./public-tier-monetization.md) — ระดับสาธารณะตัดสินครบแล้ว 15 มติ
> ขอบเขต: `course_*` (รายวิชา) + `academy_*` (โรงเรียน) + revenue share + fund model
> สถานะ: **วิเคราะห์คู่ขนานระหว่าง Codex ทำเฟส 0 ของสาธารณะ** (2026-07-24) — ยังไม่ตัดสิน รอ discuss แบบเดียวกับสาธารณะ

---

## 1. สถานะจริง — **Greenfield 100%**

| ตาราง | rows |
|---|---|
| `course_point_accounts` · `course_donates` · `course_point_campaigns` · `course_point_campaign_claims` · `course_point_transactions` · `course_point_withdrawal_requests` | **0 ทุกตาราง** |
| `academy_donates` · `academy_point_transactions` · `academy_point_rules` | **0 ทุกตาราง** |
| `academy_point_accounts` | 1 (seed) |
| `revenue_share_policies` | 1 (seed platform 60/25/10/5) |
| `adverts` ที่ `scope_type` = course/academy | **0** (ทั้ง 49 เป็น public) |

→ **ไม่มีข้อมูลจริงเลย** ต่างจากสาธารณะที่มีประวัติ 6,131 การดู + 30,444 การกดรับ
→ ระดับโรงเรียน = งาน**ออกแบบ** ล้วน ไม่มีหนี้/บั๊กข้อมูลค้างต้องล้าง (ยกเว้นบั๊กโค้ดที่ยังไม่เคยรัน)

---

## 2. แผนที่การไหลของเงิน (โครงที่สร้างไว้แล้ว)

```
                    ┌─────────── เงินเข้า (บริจาค point/cash, ส่วนแบ่งโฆษณา, allocate) ───────────┐
                    ▼                                                                              ▼
          academy_point_account ──(AcademyAllocationService)──▶ course_point_account
             │  ▲                                                    │  ▲
      academy_donates                                          course_donates
      (point→ทันที / cash→admin)                              (point→ทันที / cash→admin)
                                                                    │
                                                                    ▼
                                                    CoursePointWithdrawalRequest
                                                    (state machine เต็ม + maker-checker + payout proof)
                                                                    │
                                                                    ▼
                                              earn() → pp ของเจ้าของวิชา ──(convert 1200:1)──▶ wallet ──▶ ธนาคาร
```

**จุดสำคัญเชิงสถาปัตยกรรม:**
- **โรงเรียนถอนเป็นเงินโดยตรงไม่ได้** — ไม่มี route/service ถอนของ academy มีแค่ donations + allocations → กองทุนโรงเรียนเป็น **pass-through** ต้อง allocate ลงรายวิชาก่อน แล้วรายวิชาจึงถอน
- **การถอนของรายวิชาจ่ายออกเป็น pp** (`CoursePointWithdrawalService.php:110` `earn(...'course_withdrawal'...)`) ไม่ใช่เงินสด → เจ้าของแปลง pp→wallet เอง (นี่คือ D3 ของสาธารณะที่ยังกำกวม: บังคับแนบสลิปการโอนแต่จ่ายเป็น pp)
- Revenue share มี 4 scope: `platform > academy > course > campaign` resolve most-specific-first (`RevenueSharePolicyResolver`)

---

## 3. Mapping: มติสาธารณะ → โรงเรียน (reuse / new / ต่าง)

| มติสาธารณะ | เทียบระดับโรงเรียน | สถานะ |
|---|---|---|
| D1 โมเดลโฆษณา (จ่าย pp ได้เงิน) | โฆษณา scope=course/academy ใช้ **delivery pipeline + RevenueSharePolicy** (คนละสูตรกับ legacy public!) | ⚠️ **ต่าง** — public คงสูตร legacy, school ใช้ pipeline ใหม่ 60/25/10/5 |
| D2 กดรับแต้ม | รายวิชามี `CoursePointCampaign` (manual/lesson/quiz) + `CampaignClaimModal` 3 โหมด | ✅ สร้างแล้ว แต่โหมด donor พัง (P0 #1 สาธารณะ) |
| D2.1 พารามิเตอร์ 220/30/20 | รายวิชาใช้ `points_per_claim` ต่อแคมเปญ (เจ้าของตั้งเอง) ไม่ใช่ 270 ตายตัว | ⚠️ **ต่าง** — ต้องตัดสินว่า split ผู้กด/ผู้แนะนำ/แพลตฟอร์มใช้กับรายวิชาไหม |
| D2.2 เพดานสองชั้น | รายวิชามี `max_claims` + กัน 1 คน/แคมเปญ แต่ไม่มีเพดาน/วัน | ⚠️ ต้องเพิ่มเพดาน/วันไหม |
| D3 รับรองหนี้เดิม | ไม่มีหนี้เดิม (0 rows) | ✅ ไม่เกี่ยว |
| D6 สลิป→private | course/academy donation slip → `slip_path` เก็บ **private อยู่แล้ว** (`private/course-donation-slips/`) | ✅ **ดีกว่าสาธารณะ** — school ทำถูกตั้งแต่แรก |
| D9 เงินโอนผิด→เครดิต wallet | ต้องใช้กติกาเดียวกัน | 🔸 ตัดสินรวม |
| D11 เกม/กิจกรรม→XP | lesson/quiz reward = fund-backed (D11.2) → **นี่คือกลไกระดับรายวิชาที่ D11.2 อ้างถึง** | ✅ สะพานเชื่อม |
| P0 #8g จ่ายซ้ำ | เกิดที่ระดับรายวิชา (point_rules mint + CoursePointCampaign) | ✅ อยู่ในงาน D11.2 แล้ว |
| P1 #13 scope leak | `getMoreAdvertisings` ไม่กรอง scope → โฆษณาโรงเรียนรั่วเข้า public | 🔴 **ต้องแก้ก่อนเปิดโฆษณาโรงเรียน** |

---

## 4b. มติที่ล็อคแล้ว (user, 2026-07-24)

| # | มติ | ผลต่อ implementation |
|---|---|---|
| **DS1** | **ยุบ `campaign_type=support` ทิ้ง** — บริจาคผ่าน `course_donates`/`academy_donates` เท่านั้น | ลบ support path ใน `SupportPaymentService::distributeSupport`/`payWithWallet`, `CampaignController::store` (support branch), `StoreCampaignRequest` (type=support) → `adverts` เหลือ "โฆษณา" อย่างเดียว · แก้บั๊กจ่ายซ้ำ ~190% หายเอง |
| **DS2** | **โฆษณาโรงเรียนใช้ delivery pipeline + RevenueSharePolicy** (student 60 / course 25 / academy 10 / platform 5 เข้ากองทุนวิชา) — ต่างจาก public โดยตั้งใจ | ใช้ `AdDeliveryService` + `RewardDistributionService` (สร้างแล้ว) · **ต้อง verify จริงเพราะ 0 rows ไม่เคยรัน** · public คง legacy แยก |
| **DS3** | **ถอนเงินรายวิชา = จ่าย pp เข้าเจ้าของ** (คงโค้ดปัจจุบัน `CoursePointWithdrawalService::markPaid`) เจ้าของแปลง pp→wallet→ธนาคารเอง | ⚠️ **payout-proof slip ที่ markPaid บังคับอยู่ = ซ้ำซ้อน** (การโอนเงินจริงเกิดที่ wallet withdrawal ที่มี KYC แล้ว) → เสนอลบ requirement นี้ (รอ confirm — ดู §4c) |
| **DS4** | **การกดรับแต้มรายวิชา: ไม่หักส่วนแบ่ง** จ่าย `points_per_claim` เต็มให้ผู้เรียน | คง `grantCampaignClaim` เดิม (ไม่ใส่ platform/suggester cut แบบ public) · แพลตฟอร์มได้จากขาโฆษณา 5% เท่านั้นในระดับวิชา |

## 4c. มติเพิ่มเติม (user, 2026-07-24)
- **DS5:** ถอนวิชา = pp เข้าเจ้าของ (ภายใน) → **ลบ payout-proof slip requirement ที่ `markPaid`** (การโอนเงินจริง+KYC+สลิป เกิดตอนเจ้าของถอน wallet→ธนาคาร ที่มีอยู่แล้ว) · maker-checker คงไว้ได้
- **DS6:** บริจาคเข้ากองทุนวิชา — **บริจาคแต้ม (pp) = 1:1 ไม่หัก** (แพลตฟอร์มเก็บ spread ตอนสร้าง pp ไปแล้ว) · **บริจาคเงินสด (slip) = X×1080 pp หัก spread 10% เหมือน public** → **ต้องแก้บั๊ก cash 1:1 ที่มีอยู่** (`CourseDonateService::approve` / `AcademyDonateService::approve` credit `(int)cash_amount` 1:1)

### DS7–DS8 (user, 2026-07-24 — ล็อคครบแล้ว)
- **DS7 guest ระดับโรงเรียน:** **บริจาค → เปิด guest เลย** (schema พร้อม `course_donates.donor_id`/`academy_donates.donor_id` = nullable อยู่แล้ว) แค่ผ่อน auth ที่ slip path + lock beneficiary (course/academy) ฝั่ง server · **โฆษณา → มัดรวม epic D4** (public guest-advertiser) เพราะ `adverts.user_id` = NOT NULL ต้อง migrate + tracking link ชุดเดียวกัน
- **DS8 academy ถอนเงินตรง:** **สร้าง `AcademyPointWithdrawal`** (table + model + service + admin controller + policy + FormRequests) ขนานกับ `CoursePointWithdrawal` — จ่าย pp เข้า academy owner (ตาม DS3), ไม่มี payout-proof (ตาม DS5), maker-checker คงไว้ → โรงเรียนรับบริจาคแล้วถอนตรงได้ ไม่ต้องอ้อมผ่านวิชา

## 5b. แผน implement โรงเรียน (ตัดสินครบ 6 มติ)
**Prerequisite:** แก้ scope leak #13 (`getMoreAdvertisings` ไม่กรอง scope) ก่อนเปิดโฆษณาโรงเรียน — ไม่งั้นโฆษณา ร.ร. รั่วเข้า public
1. **DS1** ยุบ `campaign_type=support`: ลบ support branch ใน `CampaignController::store`/`StoreCampaignRequest`/`SupportPaymentService` → adverts = โฆษณาอย่างเดียว (บั๊กจ่ายซ้ำหายเอง)
2. **DS6** แก้ cash donation rate: `CourseDonateService::approve`/`AcademyDonateService::approve` เปลี่ยน `(int)cash_amount` → `cash_amount × config('economy.donation_pp_per_baht')` (point donation คง 1:1)
3. **P0 #1** `CourseDonateService::createPointDonation` เซ็ต `remaining_points = points_amount` (donor-mode claim ถึงจะทำงาน)
4. **DS5** ลบ payout-proof requirement ที่ `CoursePointWithdrawalAdminController::markPaid` + FormRequest
5. **DS2** verify ad delivery pipeline + revenue split (student 60/course 25/academy 10/platform 5) ด้วยข้อมูลทดสอบ (0 rows = ไม่เคยรัน)
6. **DS3/DS4** = คงเดิม ไม่แตะ
7. **DS7** เปิด guest บริจาค: ผ่อน auth ที่ slip path ของ course/academy donation + lock beneficiary ฝั่ง server (donor_id = null เมื่อ guest) · guest โฆษณา → epic D4 แยก
8. **DS8** สร้าง `AcademyPointWithdrawal` (mirror CoursePointWithdrawal, pp→owner, ไม่มี payout-proof)
9. schedule `risk:scan` (P1 #18 — ยกมาจาก public ด้วย)

**สรุปประเภทงาน:** ลบ/แก้ (DS1, DS6, #1, DS5, #13) = เร็ว · เปิด flag (DS7 บริจาค) = เร็ว · สร้างใหม่ (DS8 academy withdrawal) = งานจริง 1 ชุด · verify (DS2 ad pipeline) = ต้องมีข้อมูลทดสอบ · epic แยก (guest advertiser D4 = public+school) = รอบใหญ่ต่างหาก

## 4. (เดิม) สิ่งที่ต่างจากสาธารณะ — decided ด้านบนแล้ว §4b

1. **สมาชิกภาพ:** course/academy donation **บังคับ login+verified** (ต่างจากสาธารณะ D1 ที่สลิป anonymous ได้) — จะเปิด guest บริจาค/โฆษณาระดับโรงเรียนไหม? (guest advertiser D4 สาธารณะเป็นต้นแบบ)
2. **สูตรโฆษณาไม่ตรงกัน:** public = legacy (ผู้ชมจ่าย pp ได้เงิน) · school = pipeline ใหม่ (RevenueSharePolicy 60/25/10/5) — จะให้ 2 ชั้นใช้สูตรเดียวกันไหม หรือยอมให้ต่าง?
3. **แคมเปญ `support` (campaign_type=support):** ที่ระดับ course/academy มี**บั๊กจ่ายซ้ำ ~190%** (SupportPaymentService ให้ pp + แจกเงิน 70/20/10 พร้อมกัน) — ทับซ้อนกับ course_donates/academy_donates → **ยุบ support ทิ้งไหม?**
4. **การถอนของรายวิชา = pp หรือเงินสด?** (D3 สาธารณะยังค้าง) — โค้ดจ่าย pp แต่ UX บังคับสลิปโอน
5. **โรงเรียนถอนเงินได้ไหม?** ตอนนี้ทำไม่ได้ (pass-through เท่านั้น) — ถ้าต้องการต้องสร้าง `AcademyPointWithdrawalRequest` ขนาน
6. **split การกดรับ:** สาธารณะ 220/30/20 มาจากอัตราตายตัว 1080/270 — รายวิชาเจ้าของตั้ง `points_per_claim` เอง จะหักส่วนแพลตฟอร์ม/ผู้แนะนำจากแต่ละการกดรับไหม หรือกองทุนรายวิชาจ่ายเต็ม?

---

## 5. สิ่งที่สร้างไว้แล้ว (reuse ได้ทันที) vs ที่ยังขาด

**✅ สร้างแล้ว (ครบกว่าสาธารณะมาก):**
- `course_point_account` + ledger เต็ม (`course_point_transactions` มี type ครบ: donation/ad_revenue/claim/reserve/release/withdrawal)
- `CoursePointCampaign` 3 แบบ (manual/lesson/quiz) + reserve/release + กันกดซ้ำ + เช็คยอดพอ
- `CoursePointWithdrawalService` state machine เต็ม (pending→reviewing→approved→paid) + maker-checker + payout proof (private disk)
- `RevenueSharePolicy` + resolver (4 scope, versioned, sum=100 guard)
- `AcademyAllocationService` (academy→course, idempotent, locked)
- `FraudDetectionService` (self-donation, velocity, negative balance) — แต่ไม่ถูก schedule (P1 #18 สาธารณะ)
- admin UI ครบ: course-donations, academy-donations, course-withdrawals, revenue-share-policies, risk-events

**❌ ยังขาด:**
- โฆษณา scope=course/academy ยังไม่เคยถูกสร้าง/ทดสอบจริง (0 rows) — ต้อง verify delivery + revenue split ทำงานจริง
- academy withdrawal path (ถ้าตัดสินว่าต้องมี)
- reconciliation ที่ตั้ง baseline ใหม่ (เพราะ pp ledger ระดับ user ยังมีปัญหา 92% ไม่มีบรรทัด — P0 #8d สาธารณะ)
- การเชื่อม lesson/quiz reward ให้เป็นทางเดียว (D11.2) — ตอนนี้จ่ายซ้ำกับ point_rules

---

## 6. ลำดับที่แนะนำ (หลังสาธารณะเฟส 0–3 เสร็จ)

1. **รากฐานร่วม** (ออกแบบทีเดียวครอบทั้ง 2 ชั้น): `config/economy.php` SSOT, ledger fix (#8d), PP-mint policy (D11)
2. ตัดสิน 6 ข้อใน §4
3. แก้ scope leak #13 ก่อนเปิดโฆษณาโรงเรียน
4. verify course/academy ad delivery + revenue split ด้วยข้อมูลทดสอบ (เพราะ 0 rows = ไม่เคยรันจริง)
5. เชื่อม lesson/quiz reward เป็นทางเดียว (D11.2)
6. academy withdrawal (ถ้าตัดสินว่าต้องมี)

> **หมายเหตุ:** ระดับโรงเรียนโค้ดครบกว่าสาธารณะมาก (state machine, ledger, revenue share ทำไว้หมดแล้ว) แต่**ไม่เคยรันจริงเลย** — ความเสี่ยงหลักคือบั๊กที่ซ่อนอยู่ในโค้ดที่ยังไม่เคยถูกใช้ ไม่ใช่หนี้ข้อมูล
