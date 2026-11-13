# ปิดการโอนมูลค่าระหว่างผู้ใช้ (แต้ม + กระเป๋าเงิน)

> เขียนเมื่อ 2026-11-03 · สถานะ: **เป็นแผนเท่านั้น ยังไม่ได้ลงมือทำ**
> เป้าหมาย: แต้มและยอดเงินในกระเป๋า ผู้ใช้ทำได้แค่ **สะสม** และ **ถอน/ใช้จ่าย** ด้วยตัวเองเท่านั้น
> ต้องไม่มีช่องทางใดที่ผู้ใช้ A ย้ายมูลค่าไปให้บุคคล B ที่ A เลือกเองได้

---

## 1. สภาพปัจจุบัน (ตรวจจากโค้ด + DB dev แล้ว)

### 1.1 การโอนโดยตรง (เป้าหมายหลัก)

| ช่องทาง | Backend | Frontend | ประวัติใน DB dev |
|---|---|---|---|
| โอนแต้ม | `POST /api/points/transfer` → `PointsController::transfer` → `PointsService::transfer` (แถว `transfer_in` / `transfer_out`) | แท็บ "โอนแต้ม" + การ์ดหน้า overview ใน `pages/Earn/Points.vue` · `usePoints().transfer` | **โอน 412 ครั้ง รวม 1,498,376 แต้ม** (≈1,248 บาท ที่อัตรา 1200:1) ล่าสุด 2026-10-01 |
| โอนเงิน | `POST /api/wallet/transfer` → `WalletController::transfer` → `WalletService::transfer` (แถว `transfer` ทิศทางเก็บใน metadata) | `pages/Earn/Wallet.vue` · `useWallet().transfer` · `TRANSFER_MIN_AMOUNT` | 30 แถว (≈ โอน 15 ครั้ง รวมสองขา 3,632.68 บาท) ล่าสุด 2026-07-22 |
| ค้นหาผู้รับ | `GET /api/users/search` (`UserProfileController::search` คอมเมนต์เขียนว่า "for transfer") | ตัวเลือกผู้รับใน `Points.vue` **แต่ `InviteMemberModal.vue` ทั้ง 2 ไฟล์ก็ใช้ด้วย** จึงต้องเก็บ route นี้ไว้ | — |

### 1.2 การโอนทางอ้อม (ได้ผลเหมือนกันแต่ชื่อต่างกัน)

| ช่องทาง | ทำไมถึงนับเป็นการโอน | DB dev |
|---|---|---|
| **คูปองที่ผู้ใช้สร้างเอง** `POST /api/coupons` + `POST /api/coupons/redeem` (`CouponService`) | A จ่ายแต้ม/เงินเพื่อสร้างรหัสตัวเลข แล้วส่งรหัสให้ B ไปแลกรับ ทำงานเป็นการโอน**แบบไม่ระบุตัวตน** เพราะ ledger ไม่เคยผูก A กับ B ไว้ จึงแย่กว่าการโอนตรงเมื่อต้องสืบการทุจริต | แลกแล้ว 100 ใบ (24,840 แต้ม) ยังใช้งานได้อยู่ 2 ใบ |
| แคมเปญจากกองแต้มรายวิชา (`CoursePointAccountService::createCampaign`/`claimManualCampaign`) | เจ้าของรายวิชาเติมกองแต้มแล้วเลือกนักเรียนมารับ เป็นรางวัลการสอนที่ชอบธรรม แต่ทิศทางคือเจ้าของ → ผู้ใช้ที่ถูกเลือก | — |
| บริจาค → กองแต้มโรงเรียน/รายวิชา → สมาชิกกดรับเอง (`AcademyClaimService`, claim ใน `DonateController`) | ผู้บริจาค → กองกลาง → ผู้กดรับ ดีไซน์นี้เคาะไว้แล้ว (ดู memory: academy direct-claim, เจ้าของบริจาคให้ตัวเองได้) | — |
| รายได้จากการขายรายวิชา (`WalletService::purchaseCourse` เติมเงินเข้ากระเป๋าเจ้าของรายวิชา) | ผู้ซื้อ → ผู้ขาย เป็นการค้าขาย แต่ถ้าสร้างรายวิชาเองแล้วตั้งราคาเท่าไรก็ได้ ผู้ใช้ก็ย้ายเงินไปให้ผู้สมรู้ร่วมคิดได้ | — |

### 1.3 🔴 เจอระหว่างตรวจ: ช่องโหว่ที่ร้ายแรงกว่าการโอน

ข้อนี้**ไม่ใช่การโอน** ผู้ใช้ที่ล็อกอินแล้วคนไหนก็ใช้**สร้างมูลค่าขึ้นมาเอง**ได้ แล้วแปลงเป็นเงินในกระเป๋าและถอนออกไป ทุก route มีแค่ `auth:api` ไม่มีการตรวจบทบาทหรือความเป็นเจ้าของ:

| Endpoint | สิ่งที่ใครก็ทำได้ |
|---|---|
| 🔴🔴 `POST /api/forgot-password/exchange/{user}` (`ForgotPasswordController::exchangeMoney`, `routes/api.php:136`) — **เจอเพิ่ม 2026-11-04, ร้ายแรงที่สุด** | `$user->increment('pp', $request->money * 1080)` **ไม่ตรวจ `isPlearndAdmin()`** (ต่างจาก `index`/`resetPassword` ในไฟล์เดียวกัน) · **ไม่หักเงินจากไหนเลย** · `{user}` เป็นใครก็ได้ (เติมให้ตัวเอง/คนอื่น) · `money` ไม่ validate ไม่มีเพดาน (ตัวกันค่าติดลบตอบเป็น `redirect()` ใช้ไม่ได้ใน API) · **ไม่สร้างแถว `points_transactions`** จึงไม่มีร่องรอยใน ledger · UI ผู้เรียก: `ui/pages/Admin/Resetpassword.vue:345` |
| `POST /api/wallet/deposit` (`WalletController::deposit` → `WalletService::deposit`) | เติมเงินเข้ากระเป๋าตัวเอง**จำนวนเท่าไรก็ได้ตั้งแต่ 10 บาทขึ้นไป** ใส่ `method` เป็นข้อความอะไรก็ได้ ไม่ต้องแนบสลิป ไม่ต้องรอแอดมิน (ทางที่ถูกต้องคือ `/wallet/deposit-request` + แอดมินอนุมัติ) |
| `POST /api/points/refund` (`PointsController::refund`) | เพิ่มแต้มให้ตัวเอง**จำนวนเท่าไรก็ได้** `transaction_id` เป็น optional และไม่ได้ตรวจว่าเป็นของใคร |
| `POST /api/points/xp/earn` | เพิ่ม XP ได้ตามใจ (กระทบแค่เลเวล/leaderboard ไม่ใช่เงิน แต่เป็นแพทเทิร์นเดียวกัน) |
| `POST /api/points/spend`, `POST /api/wallet/deduct` | หักยอดโดยให้ client ส่ง `source_type`/`reason` มาเอง ไม่ได้สร้างมูลค่า แต่เปิดช่องให้สร้างรายการซื้อปลอมได้ |

DB dev: **ไม่มี**แถวไหนมาจาก `/wallet/deposit` เลย และแถว `refund` เดียวที่มีคือการยกเลิกคูปอง จึงดูเหมือนยังไม่ถูกใช้โจมตีบน dev **แต่ต้องตรวจ production**
ฝั่ง frontend ยังเรียก `useWallet().deposit` (→ `/wallet/deposit`) อยู่ ต้องหาก่อนว่าหน้าจอไหนใช้ ก่อนจะลบทิ้ง

### 1.4 อยู่นอกขอบเขต (จดไว้เท่านั้น)
- `budgets/{budget}/transfer` ของโรงเรียนเป็น ledger งบประมาณภายใน (ไม่เกี่ยวกับยอดของผู้ใช้) แต่น่าจะมี IDOR: `to_budget_id` ไม่ได้จำกัดให้อยู่ใน `{academy}` ควรแก้แยกต่างหาก
  · ✅ **แก้แล้ว 2026-11-12** — ยืนยันว่าเป็น IDOR จริงทั้งกลุ่ม `{academy}/budgets/{budget}` (ไม่ใช่แค่ `to_budget_id`) และ `transfer` ผูกงบผิดตัว · ใส่ `scopeBindings()` + จำกัด `to_budget_id` ให้อยู่ในโรงเรียน · เทสต์ `tests/Feature/Academy/BudgetAcademyScopingTest.php` (ดู worklog 2026-11-12)
- รางวัลจากการกดรีแอคชัน (`increment('pp', 12)` ให้เจ้าของโพสต์ ใน ~15 controller) แพลตฟอร์มเป็นผู้สร้างแต้มให้ ไม่ได้หักจากยอดของ A จึงเป็นความเสี่ยงด้าน**ปั๊มแต้ม**ด้วยบัญชีอวตาร ไม่ใช่การโอน ควรทำในรอบปรับ governance ภายหลัง
- route "transfer" ของห้องเรียน/นักเรียนเป็นเรื่องการย้ายห้อง ไม่เกี่ยวกับมูลค่า

---

## 2. เรื่องที่เจ้าของโปรเจคต้องเคาะก่อนลงมือ

| # | คำถาม | ข้อเสนอ |
|---|---|---|
| D1 | ลบโค้ดการโอนทิ้ง หรือปิดด้วย flag? | **ใช้ flag** `economy.p2p_transfers_enabled=false` (config/env) + ตอบ 403 ตายตัวที่ service layer เก็บโค้ดไว้หนึ่งรอบ release แล้วค่อยลบใน follow-up |
| D2 | คูปองที่ผู้ใช้สร้าง: ปิดทั้งการ**สร้าง**และการ**แลก**? | ปิดการ**สร้าง** ส่วนการ**แลก**ให้ใช้ต่อได้กับรหัสที่ออกไปแล้ว (2 ใบที่ยังใช้งานได้ รวม 340 แต้ม) หรือยกเลิกอัตโนมัติพร้อมคืนแต้ม คูปองที่แอดมินออก (`admin.php` `permission:coupon-create`) ยังใช้ได้ตามเดิม · ✅ **เคาะแล้ว 2026-11-12 ("เลือกตามที่แนะนำ"): ปิดการสร้างของสมาชิก · แลกได้เฉพาะคูปองแอดมิน · คูปองสมาชิกที่ค้าง → migration ยกเลิก + คืนผู้สร้าง** |
| D3 | แคมเปญรายวิชา / การกดรับเงินบริจาค / รายได้จากการขายรายวิชา อยู่ในขอบเขตไหม? | **เก็บไว้** (เป็นฟีเจอร์ที่ดีไซน์เคาะแล้ว) แต่เพิ่มด่าน: ผู้กดรับต้องเป็นสมาชิก/นักเรียนที่อนุมัติแล้ว และเฝ้าดูความถี่ ทำเป็นเฟสแยก · ✅ **เคาะแล้ว 2026-11-12 ("เลือกตามที่แนะนำ")** |
| D4 | จะทำอะไรกับการโอนแต้ม 412 ครั้งในอดีต | ไม่แตะ ledger (ต้องเก็บประวัติไว้ตรวจสอบ) ถ้าต้องการ ให้ทำรายงานครั้งเดียวว่าผู้รับรายใหญ่คนไหนแปลงเป็นเงินแล้วถอนออกไป เพื่อใช้ตรวจการทุจริต |
| D5 | ปิด `/wallet/deposit` กับ `/points/refund` ใน release เดียวกันเลยไหม? | **ใช่ และทำก่อนอย่างเร่งด่วน** เพราะร้ายแรงกว่าการโอน |
| D6 | `forgot-password/exchange` (แอดมินแลกเงินสด→แต้มให้สมาชิก) ยังใช้อยู่ไหม? เพดานต่อครั้งเท่าไร? | ✅ **เคาะแล้ว 2026-11-04: ยังใช้อยู่ เพดาน 100 บาทต่อครั้ง** (= 108,000 แต้มที่อัตรา 1080) → เก็บไว้ บังคับแอดมิน + ผ่าน ledger |

---

## 3. แผนการทำงาน

### Phase 0: ปิดช่องโหว่สร้างมูลค่าเอง (ด่วน เล็ก แยก commit)
0. ✅ **(แก้โค้ดแล้ว 2026-11-04 ยังไม่ commit · เทสต์ `ForgotPasswordExchangeMoneyTest` 7/7 บน sqlite · prod ยังไม่ได้ตรวจ) ทำก่อนข้ออื่น — `exchangeMoney`** (`ForgotPasswordController.php:196`)
   - ใส่ด่าน `isPlearndAdmin()` → 403 เป็นบรรทัดแรก (แบบเดียวกับ `resetPassword`) และย้าย route เข้ากลุ่ม middleware แอดมิน
   - validate `money` เป็น `required|numeric|min:0.01|max:100` (D6: เพดาน 100 บาทต่อครั้ง) — ตัวกันค่าติดลบแบบ `redirect()` เดิมลบทิ้ง ให้ validation ตอบ 422 แทน
   - เปลี่ยน `increment('pp')` เป็น `PointsService::adminAdjust` (หรือเทียบเท่า) ให้เกิดแถว ledger + บันทึก admin_id ผู้ทำ
   - D6 เคาะแล้ว: ฟีเจอร์ยังใช้อยู่ → **เก็บ route และปุ่มใน `Admin/Resetpassword.vue` ไว้** · UI ใส่ `max="100"` ในช่องกรอก + แสดงข้อความ 422 จาก backend
   - เทสต์เพิ่ม: แอดมินส่ง `money=100` ผ่าน · `money=100.01` ได้ 422 และ `pp` ไม่เปลี่ยน
   - ไฟล์เดียวกัน: `POST /forgot-password/getuser` ไม่ตรวจแอดมินเช่นกัน — ผู้ใช้คนไหนก็ค้นคนอื่นด้วยอีเมล/เบอร์โทร/รหัสประจำตัวได้ (PII) ใส่ด่านเดียวกันใน commit นี้
   - เทสต์: ผู้ใช้ทั่วไปได้ 403 และ `pp` ไม่เปลี่ยน · แอดมินได้ 200 และมีแถว `points_transactions` เพิ่ม 1 แถว · ผู้ใช้ทั่วไปเรียก `getuser` ได้ 403
   - **ตรวจ production**: endpoint นี้ไม่เขียน ledger จึงค้นจาก ledger ไม่ได้ → ดู access log ของ `forgot-password/exchange` + เทียบ `users.pp` กับยอดสุทธิจาก `points_transactions` รายคน (ส่วนต่างบวกที่อธิบายไม่ได้ = ผู้ต้องสงสัย)
> ✅ **ข้อ 1–3 แก้โค้ดแล้ว 2026-11-04 (ยังไม่ commit · `SelfMintEndpointsClosedTest` 8/8 บน sqlite):** ลบ `/wallet/deposit` + `/points/refund` (route + controller method; service ยังอยู่) ·
> **`/points/xp/earn` เก็บไว้** (เจ้าของเคาะ — เกม Snake/English Vocab ใช้) แต่รับเฉพาะ `config('economy.game_xp.sources')`, ≤100 ต่อครั้ง, ≤500/วัน/เกม (นับใน Cache — race เล็กน้อยยอมรับได้เพราะเป็น XP ไม่ใช่เงิน) · ข้อ 4 (ตรวจ prod): SQL เขียนไว้แล้ว รอเจ้าของรัน
> ✅ **ตรวจซ้ำ 2026-11-12 (commit `c33ef14f`):** `route:list` ไม่มี deposit/refund · `SelfMintEndpointsClosedTest` 8/8 บน **MySQL** · Pint ผ่าน · revert-check: คืนไฟล์ก่อน commit แล้ว 5 เทสต์ด่านตก ✓ · `ui/` เหลือแค่ผู้เรียก `xp/earn` (Snake, `usePoints`) ตามที่เคาะ
1. `routes/earn/points-wallet.php`: ลบ route ฝั่งผู้ใช้ `POST /wallet/deposit`, `POST /points/refund`, `POST /points/xp/earn`
   ผู้เรียกภายใน (`CouponService`, `CourseMarketplaceController`, `SchoolStoreService`) เรียกที่ **service** ไม่ใช่ route จึงไม่กระทบ ยืนยันด้วย `grep -rn "points/refund\|wallet/deposit'\|xp/earn" ui/`
2. `ui/composables/useWallet.ts`: ลบ `deposit()` แล้วหาว่าใครเรียกใช้ ถ้ามีหน้าจอไหนใช้อยู่ ให้เปลี่ยนไปใช้ `createDepositRequest`
3. เทสต์: feature test ยืนยันว่า route ที่ลบไปตอบ 404/405
4. **ตรวจ production**: นับแบบอ่านอย่างเดียว `wallet_transactions` ที่ `transaction_type='deposit'` และ reference ไม่ได้มาจากคำขอฝากเงินที่อนุมัติแล้ว กับ `points_transactions` ที่ `transaction_type='refund'` และ `source_type='refund'`
   > 🔎 **SQL พร้อมรัน (2026-11-12) — SELECT ล้วน เจ้าของรันเองบน prod** · ลองบน dev แล้ว syntax/คอลัมน์ถูก (dev ได้ 0 แถว)
   > ใครเขียนแถว `deposit`: route ที่ลบไป (method/reference/metadata ผู้ใช้กรอกเอง) · `approveDepositRequest` (ผูกกับ `wallet_deposit_requests.wallet_transaction_id`) · `CouponService` แลก/ยกเลิกคูปอง (`metadata.coupon_code`)
   > ใครเขียนแถว `refund` ที่ `source_type='refund'`: **มีแค่ route ที่ลบไป** (ผู้เรียกภายในใช้ `coupon_cancellation` / `App\Models\Course` / `reward_cancel`) จึงนับได้ตรง ๆ
   > ⚠️ ข้อจำกัด: route เก่ารับ `metadata` จากผู้ใช้ — ถ้าคนโกงใส่ `coupon_code` ที่มีอยู่จริงลงไป แถวนั้นจะหลุด Q1 ได้ (ทำได้ยาก ยอมรับ) · ถ้าเจอ `Illegal mix of collations` ใน Q1 ให้เติม `COLLATE utf8mb4_unicode_ci` หลัง `JSON_UNQUOTE(...)`
   ```sql
   -- Q1: เงินที่เข้า wallet โดยไม่มีคำขอฝากที่อนุมัติ และไม่ได้มาจากคูปอง (= ผู้ต้องสงสัยว่าเติมเองผ่าน /wallet/deposit)
   SELECT wt.id, wt.user_id, wt.amount, wt.description, wt.reference_number, wt.created_at
   FROM wallet_transactions wt
   LEFT JOIN wallet_deposit_requests r ON r.wallet_transaction_id = wt.id AND r.status = 'approved'
   LEFT JOIN coupons c ON c.coupon_code = JSON_UNQUOTE(JSON_EXTRACT(wt.metadata, '$.coupon_code'))
   WHERE wt.transaction_type = 'deposit' AND r.id IS NULL AND c.id IS NULL
   ORDER BY wt.created_at;

   -- Q1-สรุป: จำนวนแถว / ยอดรวม / จำนวนคน
   SELECT COUNT(*) AS n, COALESCE(SUM(x.amount), 0) AS total_thb, COUNT(DISTINCT x.user_id) AS users
   FROM (
     SELECT wt.id, wt.user_id, wt.amount
     FROM wallet_transactions wt
     LEFT JOIN wallet_deposit_requests r ON r.wallet_transaction_id = wt.id AND r.status = 'approved'
     LEFT JOIN coupons c ON c.coupon_code = JSON_UNQUOTE(JSON_EXTRACT(wt.metadata, '$.coupon_code'))
     WHERE wt.transaction_type = 'deposit' AND r.id IS NULL AND c.id IS NULL
   ) x;

   -- Q2: แต้มที่ผู้ใช้ "คืนให้ตัวเอง" ผ่าน /points/refund รายคน
   SELECT pt.user_id, COUNT(*) AS n, SUM(pt.amount) AS total_pp, MIN(pt.created_at) AS first_at, MAX(pt.created_at) AS last_at
   FROM points_transactions pt
   WHERE pt.transaction_type = 'refund' AND pt.source_type = 'refund'
   GROUP BY pt.user_id
   ORDER BY total_pp DESC;
   ```
   > ถ้าได้แถว → อย่าแก้ข้อมูลตรง ๆ ให้ส่งต่อเข้า `FraudRemediationService` / migration ตามกติกา (ดู Phase 4)

### Phase 1: ปิดการโอนตรง (backend คือด่านจริง)
1. `config/economy.php`: เพิ่ม `'p2p_transfers_enabled' => env('ECONOMY_P2P_TRANSFERS', false)`
2. `PointsService::transfer` และ `WalletService::transfer`: ตรวจ flag เป็นอย่างแรก ถ้าปิดอยู่ให้โยน domain exception (เช่น `AccountEconomyRestrictedException::transfersDisabled()`)
   ด่านต้องอยู่ที่ **service** เพื่อกันผู้เรียกในอนาคตด้วย ไม่ใช่แค่ route
3. `PointsController::transfer` / `WalletController::transfer`: แปลง exception นั้นเป็น **403** พร้อมข้อความไทย ("ระบบปิดการโอนแต้ม/เงินระหว่างผู้ใช้แล้ว")
   (ทางเลือก: ลบสอง route นี้ด้วย แต่ด่านที่สำคัญคือ flag + service guard)
4. เทสต์: `PointsTransferDisabledTest`, `WalletTransferDisabledTest` ทั้งคู่ยืนยันว่าได้ 403 ยอดไม่เปลี่ยน และไม่มีแถว ledger เพิ่ม รันบน MySQL (`-c phpunit.mysql.xml`)

### Phase 2: ปิดการสร้างคูปองโดยผู้ใช้
> ✅ **ทำแล้ว 2026-11-12** (เทสต์ใหม่ 15 ตัวผ่านบน sqlite · full suite ไม่มีตัวตกใหม่เทียบ `main` · ยังไม่ได้รันบน MySQL · ยังไม่ได้ตรวจจอ · ยังไม่ได้ `npm run build`)
> - ใช้ flag เดียวกับการโอนตรง `economy.p2p_transfers_enabled` · "แอดมิน" = `isPlearndAdmin()` (แบบเดียวกับ Phase 0) — `user_id` ของคูปองคือผู้สร้าง ไม่มีคอลัมน์ `created_by`
> - `POST /coupons` สมาชิกได้ 403 ก่อน validate · service โยน `memberCouponsDisabled()` · แอดมินยังสร้างได้ (หักจากยอดของแอดมินเอง)
> - redeem คูปองสมาชิก → `COUPON_MEMBER_ISSUED_DISABLED` · ผู้สร้างยังกดยกเลิกรับคืนเองได้
> - migration `2026_11_12_000001_cancel_active_member_coupons` เรียก `CouponService::cancelCoupon` (ผลเหมือนผู้สร้างกดยกเลิกเอง รวมใบที่เลยวันหมดอายุแต่ยัง active) · ข้ามคูปองเงินของคนที่ถูกระงับ wallet · ledger ติด `metadata.cancelled_by` → `down()` หักคืนแล้วเปิดคูปองกลับ (ข้ามถ้ายอดไม่พอ ไม่ทำให้ติดลบ)
> - แถม: lock แถวคูปองตอนแลก/ยกเลิก (เดิมยกเลิกพร้อมกับที่อีกคนแลก = จ่ายสองทาง)
> - 🔴 เจอ: route แอดมิน `routes/admin/admin.php` `/admin/coupons` **ใช้งานไม่ได้มาตลอด** (ใช้คอลัมน์ `code`/`creator_id`/สถานะ `unused` และ relation `creator`/`usedBy` ที่ไม่มีใน `Coupon`) → หน้า `nuxnan-admin/coupons` สร้าง/ดูคูปองไม่ได้ ทางเดียวที่แอดมินออกคูปองได้ตอนนี้คือ `POST /api/coupons` ด้วยบัญชีแอดมิน
1. `routes/earn/coupons.php`: ปิด `POST /coupons` (สร้าง) สำหรับผู้ใช้ทั่วไป (ลบ route หรือใช้ flag เดียวกัน)
2. เก็บ `POST /coupons/redeem` ไว้ แต่ให้แลกได้เฉพาะคูปองที่ `created_by` เป็นแอดมิน หรือสร้างก่อนวันตัดรอบ (ตาม D2)
3. จัดการคูปองผู้ใช้ 2 ใบที่ยังใช้งานได้ด้วย **migration** (ตามกติกาโปรเจค: แก้ข้อมูลต้องเป็น migration): ยกเลิกและคืนแต้มให้ผู้สร้างตามหลักของ `PointsService::refund` มี `down()` จริง และไม่ hard-code id
4. UI: ซ่อน/ลบหน้าสร้างคูปอง เก็บช่องกรอกรหัสแลกไว้

### Phase 3: เก็บกวาดฝั่ง frontend (ใช้กติกา mobile-first)
1. `pages/Earn/Points.vue`: ลบแท็บ `transfer`, การ์ด quick-action, การค้นหาผู้รับ, `transferForm`, `quickTransferAmounts`
   **เก็บ** label/ไอคอนประวัติของ `transfer_in`/`transfer_out` ไว้ เพื่อให้แถว ledger เก่ายังแสดงผลได้
2. `pages/Earn/Wallet.vue`: ลบฟอร์ม/โมดัลโอนเงิน และการใช้ `TRANSFER_MIN_AMOUNT` เก็บการแสดงประวัติ `transfer_in/out` ไว้
3. `usePoints.ts` / `useWallet.ts`: ลบ `transfer()` และ `TRANSFER_MIN_AMOUNT`
4. `routes/api.php:118`: แก้คอมเมนต์ของ `/users/search` (ตอนนี้เป็นการค้นหาเพื่อเชิญสมาชิก) route ยังอยู่
5. แก้ข้อความ i18n หรือคำอธิบายที่โฆษณาว่า "โอนแต้ม/โอนเงิน"
6. ตรวจที่ 375px ว่าแถบแท็บยังจัดวางถูกเมื่อแท็บหายไปหนึ่งอัน

### Phase 4: ฝั่งแอดมิน (เก็บเครื่องมือสืบสวนไว้)
- **เก็บ** มุมมองการโอนใน `AdminWalletController` (`type=transfers`), `TransactionReversal`, `FraudRemediationService` และตัวนับบนแดชบอร์ด เพราะยังต้องใช้สืบการโอน 412 + 15 ครั้งที่มีอยู่
- ทางเลือก: เปลี่ยน "ยอดโอนวันนี้" บนแดชบอร์ดแอดมินเป็นตัวเลขย้อนหลัง หรือซ่อนเมื่อ flag ปิด

### Phase 5 (แยกต่างหาก หลังเคาะ D3): เสริมความปลอดภัยช่องทางมูลค่าที่เหลือ
> ✅ **ทำแล้ว 2026-11-12 ตามคำแนะนำ D3 (เก็บฟีเจอร์ + ด่านสมาชิกที่อนุมัติ + เฝ้าดู)** — เทสต์บน sqlite · ยังไม่ได้รันบน MySQL / ตรวจจอ / `npm run build`
> - **ด่านผู้กดรับ:** `Course::canClaimPoints()` = เจ้าของ หรือ `course_members.course_member_status = 1` (คอลัมน์การอนุมัติของครู — `status` คือสถานะชำระเงิน) ใช้กับกองบริจาครายวิชา, แคมเปญ manual, `grantCampaignClaim` (ครอบรางวัลบทเรียน/แบบทดสอบ), endpoint view/available · เดิมแค่มีแถว `course_members` (รออนุมัติ) ก็กดรับได้ · ฝั่งโรงเรียนบังคับ `academy_members.status=2` อยู่แล้ว
> - **เฝ้าดูตอนถอน:** `WithdrawalFundSourceService` เห็นแต้มที่กดรับจากกอง (ระบุผู้บริจาค/ผู้สร้างแคมเปญ) และรายได้ขายรายวิชา (ระบุผู้ซื้อ) · ถ้าคนเดียวคิดเป็น ≥50% ของยอดถอน → `single_indirect_funder` ระดับ high · ไม่นับมูลค่าที่มาจากตัวเอง
> - ⏸️ **ยังไม่ทำ — ต้องให้เจ้าของเคาะตัวเลข (ไม่มีในข้อเสนอเดิม):**
>   - (P5-a) 🔴 เพดาน `points_per_claim` ของแคมเปญรายวิชา — ตอนนี้ `min:1` ไม่มี max: เจ้าของตั้งแคมเปญก้อนเดียวเท่ากองทั้งกอง แล้วให้สมาชิกที่ตัวเองอนุมัติกดรับไปได้ทันที (ด่านสมาชิกกันไม่ได้เพราะเจ้าของเป็นคนอนุมัติเอง · การถอนเป็นเงินจะโดนจับที่ trace แต่การใช้แต้มซื้อของไม่โดน)
>   - (P5-b) พักรายได้ขายรายวิชา N วันก่อนถอนได้
>   - (P5-c) จำกัดการแปลง แต้ม→เงิน สำหรับแต้มที่มาจากกองกลาง
- รายได้จากการขายรายวิชา: พักเงินของเจ้าของไว้ N วันก่อนถอนได้ หรือ flag การขายที่ผู้ซื้อกับผู้ขายใช้ IP/อุปกรณ์เดียวกัน
- การกดรับแคมเปญ/เงินบริจาค: ยืนยันว่าผู้กดรับเป็นสมาชิกที่อนุมัติแล้ว (`academy_members.status=2`) หรือเป็นนักเรียนที่ลงทะเบียน และจำกัดความถี่ต่อผู้กดรับ
- จำกัดอัตราการแปลง `แต้ม → เงิน` สำหรับบัญชีที่แต้มมาจากการกดรับจากกองกลาง

---

## 4. เกณฑ์การยอมรับ
- [ ] `POST /api/points/transfer` และ `/api/wallet/transfer` → 403 (หรือ 404) ยอดไม่เปลี่ยน (เทสต์บน MySQL)
- [ ] `POST /api/wallet/deposit`, `/api/points/refund`, `/api/points/xp/earn` → 404/405
- [ ] ผู้ใช้ทั่วไปสร้างคูปองไม่ได้ คูปองของแอดมินยังแลกได้
- [ ] Points.vue และ Wallet.vue ไม่มี UI การโอน แถวประวัติการโอนเก่ายังแสดงถูกต้องที่ 375px
- [ ] การค้นหาผู้ใช้ใน `InviteMemberModal` ยังใช้งานได้
- [ ] มุมมองสืบการทุจริตของแอดมินยังแสดงการโอนย้อนหลัง และการ reverse ยังใช้ได้
- [ ] `grep -rn "points/transfer\|wallet/transfer" ui/` → ไม่พบ

## 5. ลำดับ commit (revert ได้ทีละชุด)
1. `fix(economy): ปิด endpoint ที่ผู้ใช้สร้างเงิน/แต้มเองได้ (deposit/refund/xp)` (Phase 0)
2. `feat(economy): ปิดการโอนแต้ม/เงินระหว่างผู้ใช้ที่ service layer + flag` (Phase 1)
3. `feat(coupons): ปิดการสร้างคูปองโดยผู้ใช้ + migration ยกเลิกคูปองค้าง` (Phase 2)
4. `refactor(earn-ui): เอา UI โอนแต้ม/โอนเงินออก` (Phase 3)
