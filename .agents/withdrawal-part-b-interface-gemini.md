# Part B — Interface / API / Admin & Frontend (สำหรับ Google Gemini)

> คุณคือผู้รับผิดชอบ **ชั้น Interface**: API controllers, routes, authorization, admin/user endpoints และ **frontend Vue ทั้งหมด** ของการปรับปรุงระบบถอนเงิน nuxnan
> อ่านแผนแม่ฉบับเต็มก่อนเสมอ: [`withdrawal-system-hardening-plan.md`](withdrawal-system-hardening-plan.md)
> โดยเฉพาะ **Shared Contract** และ **Runbook** — ยึดเป็นสัญญาที่ตรึงไว้ ห้ามเปลี่ยนฝ่ายเดียว

## บริบทโปรเจกต์
- Backend: Laravel 12 + PHP 8.4 ที่ `api/nuxnanravel/` — auth = JWT (`auth:api`)
- Frontend: Nuxt 3 + Vue 3 (`<script setup lang="ts">`) + Pinia + Tailwind + PrimeVue ที่ `ui/`
  - เรียก API ผ่าน composable (`useApi`/`useWallet`/`useAdminWallet`) **ห้าม `$fetch` ตรง**
- Code style: `./vendor/bin/pint --dirty` (backend), build check (frontend) ก่อน commit

## ไฟล์ที่คุณเป็นเจ้าของ (แก้ได้)
- `app/Http/Controllers/Api/WalletController.php`
- `app/Http/Controllers/Api/AdminWalletController.php`
- `app/Policies/WithdrawalPolicy.php` (สร้างใหม่) + register ใน `AuthServiceProvider`
- `routes/earn/points-wallet.php`, `routes/admin/admin.php`
- `config/wallet.php`
- `ui/**` — 7 ไฟล์ (ดู Decisions ตาราง Frontend ในแผนแม่)
- tests: `tests/Feature/WalletHttp*`, `ui/tests/*`

## ห้ามแตะ (เป็นของ Codex)
- `app/Services/WalletService.php` internals, `app/Models/WalletTransaction.php` (schema/casts), `database/migrations/**`, `app/Console/Commands/Wallet*`
- คุณ **เรียกใช้** WalletService ผ่าน signature ใน Shared Contract §B เท่านั้น

## บั๊กที่ต้องแก้ (ยืนยันแล้ว)
- **ข้อ 13 (สิทธิ์)**: `approveWithdrawal()` ใช้ `isSuperAdmin()` แต่ `rejectWithdrawal()` ใช้ `isAdminUser()` → ไม่สมมาตร แก้ด้วย `WithdrawalPolicy` เดียว (Decision 5: approve/reject = SUPER_ADMIN+ADMIN, MODERATOR = view only)
- **ข้อ 14 (response)**: เลิก hardcode `'status' => 'completed'/'cancelled'` → `$tx->refresh()->status`
- **ข้อ 15 (stats)**: แก้ทุก query ใน `stats()` ให้รองรับ enum 9 ค่า
- **ข้อ 16 + route coupling**: frontend เรียก `/api/admin/wallet/...` (ชุด admin.php) → เมื่อรวม route ต้อง **repoint frontend คอมมิตเดียวกัน**

## งานของคุณตามลำดับ (อ้าง section ในแผนแม่)
| Step | งาน | Gate |
|------|-----|------|
| 0 | อ่าน Shared Contract + ตกลง config keys §D | contract ตรงกัน |
| 1.5 | Controllers รวมศูนย์ + WithdrawalPolicy + แก้ auth/response/stats + ลบ route ซ้ำ + **repoint frontend** (§1.4, Step 1.5) | route เหลือชุดเดียว, หน้า admin กด approve/reject ได้จริง |
| 3.1 | Config keys + sync `useWallet.ts` (หรือทำ `GET /wallet/config`) (§3.4) | daily_limit test เขียว |
| 3.3 | Maker-checker เช็คใน controller (§3.3) | same-admin blocked test เขียว |
| 3.4 | ไม่ leak ข้อมูลบัญชี — response แสดงเฉพาะ masked (§3.4) | user_cannot_view_others test เขียว |
| 3.5 | Admin endpoints ใหม่ + routes (show/process/mark-paid/mark-failed/audit-log/all) (§3.5) | route:list ครบ, smoke test |
| 3.6 | User endpoints: cancel/myWithdrawals + routes (§3.6) | cancel คืนเงินได้ |
| 4.3 | Dashboard stats ใหม่ + CSV export UI (§4.4) | dashboard แสดงถูก |
| FE | Frontend 7 ไฟล์: ขยาย status union 9 ค่า, badge/label/สี, ปุ่ม lifecycle, locked balance, cancel | build ผ่าน + preview ใช้งานได้ |

## สิ่งที่คุณต้องยึดจาก Shared Contract
- **§B**: เรียก WalletService ตาม signature เป๊ะ; แปลง exception → HTTP: `DomainException`→422, `RuntimeException`→409, `false/null`→400
- **§D**: คุณเป็นคนนิยามค่า config (ตาม Decisions) ให้ Codex อ่าน
- **§E**: response envelope `{success, message, data}`; `status` อ่านจาก refresh เสมอ
- **§A**: status union ใน frontend = 9 ค่าเป๊ะ

## Tests สำคัญที่คุณต้องเขียนให้เขียว
`unauthorized_admin_rejected`, `moderator_cannot_approve_or_reject`, `maker_checker_same_admin_blocked`, `user_cannot_view_others_account`, `bank_account_masked_in_response`, `daily_limit_enforcement`, `cancel_returns_money_to_available` (ผ่าน HTTP), `pending_policy_blocks_second_request` (ผ่าน HTTP)

## กฎเหล็ก
- อ่านไฟล์ก่อนแก้เสมอ; frontend เรียก API ผ่าน composable เท่านั้น
- **ห้าม merge Part B ก่อน Part A ใน phase เดียวกัน** (controller เรียก service ที่ยังไม่มี) — code ขนานได้ แต่ integrate หลัง Codex
- Frontend repoint route ต้องอยู่คอมมิตเดียวกับการลบ route backend
- ถ้าต้องเปลี่ยน Shared Contract → หยุด แจ้ง แล้วแก้ทั้งสองฝั่งพร้อมกัน
