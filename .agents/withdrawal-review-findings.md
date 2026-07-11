# ผลตรวจสอบโค้ดระบบถอนเงิน (Verification Findings) — 2026-07-11

ตรวจงานที่ Codex (Part A) + Gemini (Part B) ส่งมอบ เทียบกับ Shared Contract และ Runbook

> [!IMPORTANT]
> **อัปเดต 2026-07-11 (รอบแก้):** CRITICAL C1–C3 และ HIGH H1–H2 **แก้แล้วและมี test ยืนยัน** (`tests/Feature/Wallet/WithdrawalHardeningTest.php` — 7 tests ผ่าน; wallet suite 27 ผ่าน) รายละเอียดการแก้อยู่ท้ายไฟล์ (§Resolution) — ส่วน MEDIUM (Phase 2/4, endpoints ที่เหลือ) **เลื่อนไว้ตามที่ผู้ใช้อนุมัติ**

**สรุปเดิม: ยังไม่ผ่าน — พบบั๊กระดับวิกฤต 3 จุดที่ทำเงินหาย/ยอดไม่ตรง ห้าม deploy จนกว่าจะแก้**

## 🔴 CRITICAL — ต้องแก้ก่อนไปต่อ (money loss / integrity)

### C1. `markWithdrawalFailed()` ไม่คืนเงิน — เงินผู้ใช้หายถาวร
`WalletService::markWithdrawalFailed()` เปลี่ยนสถานะเป็น `failed` เฉย ๆ แต่ `withdraw()` หักเงินออกจาก `wallet` ไปแล้วตั้งแต่ตอนสร้างคำขอ เมื่อการโอนจริงล้มเหลว (approved/processing → failed) เงินไม่ถูกคืน → ผู้ใช้เสียเงินทั้งที่ไม่ได้รับการโอน
- Runbook/แผนระบุชัด: `approved/processing -> failed -> ต้องคืนเงิน` + compensating ledger
- **แก้:** ใน transaction เดียวกัน ให้ lock user + คืน `wallet += amount` + สร้าง ledger `refund` (แบบเดียวกับ reject)

### C2. `cancelWithdrawal()` คืนเงินแต่ไม่สร้าง ledger — ยอดกับประวัติไม่ตรง
`cancelWithdrawal()` ทำ `wallet += amount` แล้ว set `cancelled` แต่ **ไม่สร้าง compensating transaction** (ต่างจาก `rejectWithdrawal()` ที่สร้าง refund ledger) → reconciliation `sum(ledger) == wallet` จะเพี้ยน และ balance_before/after ของแถว withdraw เดิมค้างเป็นค่าเก่า
- **แก้:** ให้ cancel สร้าง refund ledger เหมือน reject (ต้องใช้วิธีเดียวกันทั้งคู่ให้ consistent)

### C3. Maker-Checker เป็น dead code — คุมยอดสูงไม่ได้จริง
`approveWithdrawal()` เช็ค `$tx->reviewed_by === $reviewer->id` แต่ `reviewed_by` เป็น `null` จนกว่าจะ approve/reject; `viewWithdrawal()` ก็ไม่ได้ set `reviewed_by` (แค่ log) → ไม่มีขั้น "review แรก" ที่ตั้งค่า reviewer ก่อน ดังนั้น Admin คนเดียวอนุมัติยอด ≥ 10,000 ได้เสมอ **การควบคุมสองคนไม่เคยทำงาน**
- **แก้:** นิยาม flow สองขั้นจริง เช่นให้ action review/hold set `reviewed_by` (→ under_review) แล้ว approve บังคับให้เป็นคนละคน หรือเก็บ first-actor แยกฟิลด์

## 🟠 HIGH — security / ผิด contract

### H1. สิทธิ์ยังไม่สมมาตร (Bug 13 แก้ไม่ครบ) + ไม่ได้ใช้ Policy
- `approveWithdrawal` = `isSuperAdmin() || hasRole('ADMIN')` ✓
- `rejectWithdrawal` = ยังใช้ `isAdminUser()` → **MODERATOR/PlearndAdmin ปฏิเสธ (ขยับเงิน/คืนเงิน) ได้ แต่อนุมัติไม่ได้** ขัด Decision 5 (MODERATOR = ดูอย่างเดียว)
- สร้าง `WithdrawalPolicy` (กติกาถูกต้อง) + register แล้ว แต่ **controller ไม่เรียกใช้ policy เลย** ใช้ inline check แทน → policy เป็น dead code
- **แก้:** ใช้ `$this->authorize('reject'/'approve', $tx)` ทุก endpoint ให้เป็นแหล่งสิทธิ์เดียว ลบ inline ที่ไม่ตรง

### H2. `daily_limit` / `monthly_limit` ตั้ง config แล้วแต่ไม่ถูกบังคับ
`withdraw()` เช็คแค่ `max_pending` ไม่มีการตรวจยอดสะสมต่อวัน/เดือน → config เป็นแค่ตัวเลขลอย gate `daily_limit_enforcement` fail
- **แก้:** บังคับใน `withdraw()` (sum ยอดวันนี้/เดือนนี้ ภายใต้ lock) เกิน → throw `DomainException`

## 🟡 MEDIUM — งานที่ยังไม่ครบตามแผน

- **M1. Phase 2 ไม่ได้ทำ:** ยังใช้ `(float)` + `number_format` ไม่ได้ใช้ bcmath; ไม่มี `BcMathHelper`; ไม่มีคอลัมน์ `locked_balance` (ใช้วิธี derive จาก sum pending แทน — ใช้ได้แต่ต้อง consistent ดู C2); ไม่ได้ขยาย precision เป็น decimal(15,2)
- **M2. Phase 4 ไม่ได้ทำ:** ไม่มี `WalletReconciliationService`, ไม่มี `wallet:reconcile` / `wallet:stale-pending`, ไม่มี scheduler → ตรวจ wallet↔ledger อัตโนมัติไม่ได้ (สำคัญก่อน production)
- **M3. Endpoint/route ขาด:** `viewWithdrawal` (under_review) และ `cancelWithdrawal` (user) มี method ใน service แต่**ไม่มี route** → ผู้ใช้ยกเลิกไม่ได้; ขาด show/all/audit-log/myWithdrawals (Step 3.5/3.6 ทำไม่ครบ)
- **M4. Migration 000002 ไม่มี backfill** แถว `cancelled` เดิม (Step 1.2) และ `down()` จะ fail/truncate ถ้ามีแถวสถานะใหม่
- **M5. Dead code:** `WalletController::approveWithdrawal/rejectWithdrawal` เหลือ method อยู่แต่ไม่มี route ชี้แล้ว — ควรลบ
- **M6. ไม่มี automated test ใหม่เลย** — test cases ใน contract (concurrent_withdrawals_no_overdraft ฯลฯ) ไม่มี → ทุก verification gate ยังพิสูจน์ไม่ได้

## 🟢 LOW
- frontend ใช้ `$fetch` ตรง (ผิด convention `useApi`) — เป็น pattern เดิมของไฟล์
- string compare `$balanceBefore < $amount` พึ่ง numeric-string coercion — ทำงานได้แต่เปราะ
- ข้อดี: Gemini แก้บั๊กเดิม `calculateFee` 13% → 0.5% ถูกต้องแล้ว

## สิ่งที่ผ่าน ✓
- Migration 000001 (fields + unique idempotency_key + indexes + FK) ครบถูกต้อง
- Enum 9 สถานะ, Model fillable/casts/canTransitionTo/reviewer, AuditLog signature เรียกถูก
- Route ซ้ำ 3 ชุด → รวมเหลือชุดเดียว (admin.php) + repoint frontend แล้ว
- approve/reject/process/paid ใช้ `lockForUpdate()` + version bump + audit ครบ
- reject สร้าง refund ledger + audit `withdrawal.refunded` ถูกต้อง (ใช้เป็นต้นแบบให้ C1/C2)

---

## Resolution (รอบแก้ 2026-07-11)

แก้โดย Claude ตามที่ผู้ใช้อนุมัติ (แก้ CRITICAL/HIGH เอง, เลื่อน Phase 2/4)

| # | สถานะ | สิ่งที่แก้ | Test |
|---|-------|-----------|------|
| C1 | ✅ แก้แล้ว | `markWithdrawalFailed()` คืนเงิน + สร้าง refund ledger (ผ่าน helper `refundWithdrawalToWallet()`) ภายใต้ lock user | `failed_withdrawal_refunds_money_with_ledger_entry` |
| C2 | ✅ แก้แล้ว | `cancelWithdrawal()` สร้าง refund ledger + guard `transaction_type` เหมือน reject | `cancel_withdrawal_refunds_money_with_ledger_entry` |
| C3 | ✅ แก้แล้ว | Maker-checker ใช้งานได้จริง: `viewWithdrawal()` set `reviewed_by` + ย้าย under_review; approve ยอด ≥ threshold บังคับผู้ตรวจคนแรก ≠ ผู้อนุมัติ; approver เก็บใน `metadata.approved_by` + เพิ่ม route `GET /withdrawals/{id}` (showWithdrawal) | `maker_checker_blocks_single_admin_for_high_value`, `maker_checker_allows_two_distinct_admins`, `low_value_withdrawal_approves_with_single_admin` |
| H1 | ✅ แก้แล้ว | ทุก endpoint (approve/reject/process/paid/failed/show) ใช้ `WithdrawalPolicy` ผ่าน `$user->can()`; reject เลิกใช้ `isAdminUser()`; DomainException→422, RuntimeException→409 (`mapWithdrawalException`) | `withdrawal_authorization_is_symmetric` |
| H2 | ✅ แก้แล้ว | `assertWithinWithdrawLimits()` บังคับ daily/monthly limit ใน `withdraw()` (นับเฉพาะสถานะที่ยังถือเงินไว้) | `daily_limit_is_enforced` |
| PII | ✅ แก้แล้ว | metadata เก็บเลขบัญชี masked เท่านั้น (`maskBankAccount()`); เลขเต็มอยู่ใน `destination_snapshot` ที่เข้ารหัส; อัปเดต WithdrawTest 2 เคสให้ตรง | `WithdrawTest` (masked + decrypt snapshot) |

### ยังค้าง (เลื่อนตามมติผู้ใช้ — เอา Phase 1 ให้แน่นก่อน)
- **M1 Phase 2** (bcmath, locked_balance column, decimal(15,2)) — ยังใช้ float+number_format
- **M2 Phase 4** (WalletReconciliationService, commands, scheduler)
- **M3** endpoints ที่เหลือ: user `cancelWithdrawal`/`myWithdrawals` (service มีแล้ว แต่ยังไม่มี route ฝั่ง user), `allWithdrawals`, `audit-log`
- **M4** migration 000002 ไม่มี backfill legacy `cancelled` + `down()` ไม่ guard สถานะใหม่
- **M5** dead code: `WalletController::approveWithdrawal/rejectWithdrawal` (ไม่มี route ชี้แล้ว ควรลบ)
- **นอกขอบเขต:** `WalletAndPointsTest > user can earn points` fail อยู่ก่อนแล้ว (PointsService query "Illegal operator and value combination") — ไม่เกี่ยวกับ withdrawal

### การตรวจสอบ
- `tests/Feature/Wallet/WithdrawalHardeningTest.php` — 7 passed (30 assertions)
- `--filter=Wallet` — 27 passed, 1 pre-existing fail (points, ไม่เกี่ยว)
- `pint --dirty` — passed; `route:list` — withdrawal เหลือชุดเดียว ไม่ซ้ำ

---

## Deep Money-Accounting Audit (รอบ 3 — 2026-07-11)

ตรวจเชิงลึกตามคำขอ: "เงินออกต้องไม่เกินเงินเข้า" + สรุปเงินเข้า-ออก 9 สถานะ

### 🔴 พบช่องโหว่ระบบ: มีแค่ `withdraw` ที่ล็อก — จุดลดเงินอื่นไม่ล็อก (แก้แล้ว)
`withdraw`/reject/cancel/failed ล็อก user แล้ว แต่ `transfer`, `convertWalletToPoints`, `purchaseCourse`,
`adminAdjust`, `deposit`, `addFromPointsConversion` ยัง read-modify-write **โดยไม่ล็อก** →
ใน InnoDB REPEATABLE READ การอ่านแบบไม่ล็อกไม่รอ lock ของ withdraw → **race ทำให้เงินออกเกินยอด/ติดลบได้**

**แก้แล้ว:** เพิ่ม `lockUser()` / `lockUsers()` / `lockUserPair()` (ล็อกตามลำดับ id กัน deadlock) ใน
**ทุก** method ที่แตะ `users.wallet`; `addFromPointsConversion` ห่อ `DB::transaction` เพิ่ม (เดิมไม่มี)

### เครื่องมือ Reconciliation (ใหม่)
- `app/Services/WalletReconciliationService.php` — สรุป money_in/out, ยอดถอน 9 สถานะ, held/paid/returned,
  ตรวจ `wallet == Σ(balance_after-balance_before)` ทุก user, ยอดติดลบ, และ refund integrity
- `php artisan wallet:reconcile [--user=ID] [--mismatched]` — คืน exit code ≠ 0 ถ้าไม่ healthy (ใช้ monitor/CI ได้)
- **หลักการ:** ทุกการแก้ wallet มี ledger row ที่ delta = การเปลี่ยนแปลงจริง → `Σ wallet == money_in - money_out`
  และ money_out ≤ money_in เสมอถ้าไม่มียอดติดลบ

### ผลรันบน DB dev จริง (สำคัญ — ต้อง baseline ก่อน production)
```
Money in 2229.13 | Money out 2658.74 | Net -429.61 | Σ wallets 4740.96
Money out ≤ money in: VIOLATION | Wallets==ledger: MISMATCH
Mismatched users: 385 | Negative balances: 0
Returned withdrawals 248.99 vs Refunds 0.00 (MISMATCH)
```
**ตีความ:** ไม่ใช่เงินจริงหาย แต่ **wallet เก่าถูกตั้งค่าโดยไม่มี ledger ครบ** (seeder/points/reward เก่า)
+ 2 รายการ cancelled เก่าไม่มี refund ledger (โค้ด reject เดิม) → ข้อมูล baseline ไม่ครบ
- ✅ 0 ยอดติดลบ = ไม่มีใครถอนเกินตอนนี้
- ⚠️ ต้องทำ **opening-balance baseline** (บันทึก wallet ปัจจุบันเป็น ledger จุดตั้งต้น) ก่อนเปิดถอนเงินจริง
  จากนั้น reconcile จะเป็น 0 diff และ drift ใด ๆ ต่อจากนี้ = bug จริงที่จับได้ทันที

### พิสูจน์โค้ดใหม่ถูกต้อง (DB สะอาด)
`tests/Feature/Wallet/WalletReconciliationTest.php` — 2 tests:
- full lifecycle (deposit→paid/reject/cancel/failed→transfer): money_out ≤ money_in, wallet==ledger,
  refund integrity balanced, healthy=true
- ทุก 9 สถานะเก็บ count+amount ครบ; held คำนวณถูก
ผลรวมทดสอบ: Wallet suite 28 ผ่าน + reconciliation 2 + hardening 7 + course purchase 25 = ผ่านทั้งหมด
(เหลือ `user can earn points` fail อยู่ก่อนแล้ว ไม่เกี่ยว)

### ยังต้องทำก่อน production เงินจริง
1. **Opening-balance baseline migration** ให้ wallet ทุก user มี ledger ตั้งต้น (ต้องขออนุมัติ — เขียนลง ledger จริง)
2. Backfill/ทำเครื่องหมาย 2 รายการ cancelled เก่าที่ไม่มี refund
3. ตั้ง `wallet:reconcile` เป็น scheduled job รายวัน + alert เมื่อไม่ healthy
4. Load test ถอนพร้อมกันจริงหลายเครื่อง (row-lock พิสูจน์เต็มรูปแบบต้องใช้หลาย process — unit test ทำไม่ได้)

---

## Execution บน DB จริง (2026-07-12) — Baseline + Legacy + Deployment

ดำเนินการตามที่ผู้ใช้อนุมัติ commit บน DB จริง (nuxnan)

### สิ่งที่สร้าง/ทำ
- `WalletService::recordOpeningBalance()` + `flagLegacyWithdrawal()` (ล็อก + audit, ไม่ขยับเงิน)
- `WalletReconciliationService::checkWithdrawalRefundIntegrity()` ข้ามรายการ flag legacy
- Commands: `wallet:baseline [--commit --force --user=]`, `wallet:flag-legacy-withdrawals [--commit]`
- Migration `..._add_opening_balance_transaction_type` (enum transaction_type)
- Tests: baseline reconcile + idempotent, legacy-flag clears integrity (Wallet suite 25 ผ่าน)

### ลำดับที่รัน (มี backup ในตัว DB ก่อน: `wallet_transactions_bak_20260711`, `users_wallet_bak_20260711`)
1. migrate 000003 (enum opening_balance)
2. `wallet:flag-legacy-withdrawals --commit` → flag 2 รายการ (id 12=100.00, id 185=148.99)
3. `wallet:baseline --commit --force` → บันทึก **385 opening_balance** (Σ +5,170.59, ไม่ขยับเงิน)
4. normalize 165 wallets ที่มี float ขยะ → align wallet = ledger sum (source of truth) exact
5. **พบช่องโหว่: migration 000001/000002 ไม่เคยรันบน dev** (Codex สร้างไฟล์แต่ไม่รัน) → รันให้ครบ

### ผลสุดท้าย — Ledger HEALTHY ✅
```
Money in 7399.72 | Money out 2658.74 | Net 4740.98 | Σ wallets 4740.98
Money out ≤ money in: OK | Wallets == ledger: OK
Mismatched users: 0 | Negative balances: 0
Withdrawal refund integrity: 0.00 vs 0.00 (OK)
```
Schema ครบ: คอลัมน์ hardening ทั้งหมด + status enum 9 ค่า; migration 000001/2/3 = Ran

### หมายเหตุ/ค้าง
- 32 soft-deleted users ถือ wallet รวม 4.75 แต่ 0 transactions (บัญชีลบแล้ว, reconcile scope กันไว้) — ไม่กระทบ
- backup tables `*_bak_20260711` เก็บไว้ ลบได้เมื่อมั่นใจ (`DROP TABLE ...`)
- แนะนำตั้ง `wallet:reconcile` เป็น scheduled job รายวัน + alert เมื่อไม่ healthy
