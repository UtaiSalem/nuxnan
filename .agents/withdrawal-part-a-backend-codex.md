# Part A — Backend Money Engine (สำหรับ Codex / ChatGPT)

> คุณคือผู้รับผิดชอบ **แกนระบบการเงิน (Money Engine)** ของการปรับปรุงระบบถอนเงิน nuxnan
> อ่านแผนแม่ฉบับเต็มก่อนเสมอ: [`withdrawal-system-hardening-plan.md`](withdrawal-system-hardening-plan.md)
> โดยเฉพาะ **Shared Contract** และ **Runbook** — ยึดเป็นสัญญาที่ตรึงไว้ ห้ามเปลี่ยนฝ่ายเดียว

## บริบทโปรเจกต์
- Laravel 12 + PHP 8.4, DB MySQL `nuxnan`, รันบน WAMP (`C:\wamp64\`)
- Backend อยู่ที่ `api/nuxnanravel/` — รัน `php artisan ...` ในโฟลเดอร์นี้
- Code style: `./vendor/bin/pint --dirty` ก่อน commit เสมอ
- เงินจริง: **ห้ามผิดพลาดเด็ดขาด** ทุก operation ต้องอยู่ใน `DB::transaction()` + `lockForUpdate()`

## ไฟล์ที่คุณเป็นเจ้าของ (แก้ได้)
- `app/Services/WalletService.php`
- `app/Services/WalletReconciliationService.php` (สร้างใหม่)
- `app/Models/WalletTransaction.php`
- `app/Helpers/BcMathHelper.php` (สร้างใหม่)
- `database/migrations/*` (ไฟล์ migration ใหม่ทั้งหมด)
- `app/Console/Commands/Wallet*.php` (สร้างใหม่)
- tests: `tests/Unit/Wallet*`, `tests/Feature/WalletService*`

## ห้ามแตะ (เป็นของ Gemini)
- `app/Http/Controllers/**`, `app/Policies/**`, `routes/**`, `config/wallet.php`, `ui/**`

## งานของคุณตามลำดับ (อ้าง section ในแผนแม่)
| Step | งาน | Gate |
|------|-----|------|
| 0 | อ่าน Shared Contract + backup DB + สร้าง characterization test | contract เข้าใจตรงกัน |
| 1.1 | Migration เพิ่ม withdrawal fields (§1.1) | up/down สมมาตร |
| 1.2 | Migration เปลี่ยน status enum 9 ค่า + data migration (§1.2) | ข้อมูลเก่าไม่ตกหล่น |
| 1.3 | Model: fillable/casts/reviewer/canTransitionTo/Auditable (§1.5) | unit test เขียว |
| 1.4 | Service: withdraw/approve/reject atomic + lock + refund ledger + audit (§1.3) | concurrency tests เขียว |
| 2.1 | BcMathHelper + autoload (§2.5) | rounding test เขียว |
| 2.2 | Migration locked_balance + ขยาย precision decimal(15,2) (§2.1) | ยอดเดิมไม่เปลี่ยน |
| 2.3 | Service: available+locked balance (§2.2, §2.4) | locked matches pending |
| 2.4 | Service: signature float→string + bcmath ทั้งชุด (§2.3) | wallet suite ไม่ regress |
| 3.2* | Service: 5 lifecycle methods + maker-checker logic + encrypt/mask (§3.2) | state machine test เขียว |
| 4.1 | WalletReconciliationService (§4.1) | reconcile 0 mismatch |
| 4.2 | Artisan commands + scheduler (§4.2, §4.3) | command รันได้จริง |

\* Step 3.2 คุณทำ **เฉพาะ method ใน WalletService** ส่วน endpoint/route ที่เรียก method เหล่านี้เป็นของ Gemini

## สัญญาที่คุณต้องส่งมอบ (ให้ Gemini เรียกใช้)
ยึดตาม **Shared Contract §B/C** ในแผนแม่เป๊ะ ๆ:
- Method signatures ตามที่ระบุ (return `false`/`null` / throw `DomainException` / `RuntimeException`)
- ยิง audit event ทุก transition ตามชื่อใน §C
- อ่าน config keys ใน §D (Gemini เป็นคนนิยามค่า) — อย่า hardcode

## Tests สำคัญที่คุณต้องเขียนให้เขียว (ดูตาราง Verification ในแผนแม่)
`concurrent_withdrawals_no_overdraft`, `concurrent_approve_only_one_succeeds`, `concurrent_reject_refunds_once`, `idempotency_key_prevents_duplicate`, `approve_rejected_fails`, `refund_creates_ledger_entry`, `rollback_preserves_consistency`, `locked_balance_matches_pending`, `bcmath_no_rounding_errors`, `state_machine_prevents_invalid_transitions`, `reconciliation_after_lifecycle`

## กฎเหล็ก
- อ่านไฟล์ก่อนแก้เสมอ
- 1 step = 1 commit ที่ revert ได้
- ห้าม `migrate:fresh` บน DB ที่มี data จริง
- ถ้าต้องเปลี่ยน Shared Contract → หยุด แจ้ง แล้วแก้ทั้งสองฝั่งพร้อมกัน
