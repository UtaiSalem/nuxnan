# Course Donation + Wallet Ledger — Phase 0 Audit (2026-07-18)

Audit was performed by Claude inline (Codex process died after ~2 min log activity; status stuck "running" for 48 min). This report is the deliverable Codex was originally asked to produce, corrected against what actually exists in the repo.

## TL;DR — the big correction

The original 12-phase plan assumed the codebase had almost nothing (no ledger, no idempotency, no reservation). **That is wrong.** The system already has strong ledger discipline in `WalletService` and `CoursePointAccountService`. The remaining gap is much narrower than the plan describes.

**Re-scoped work is roughly ~3-4 weeks of focused engineering, not 6 months.**

## 1. What already exists (with citations)

### 1.1 WalletService (`api/nuxnanravel/app/Services/WalletService.php`)

User-scoped cash wallet with production-grade ledger discipline:

- Locking: `WalletService::lockUser` at line 923, `lockUserPair` (deadlock-safe) at line 948; used in `deposit` (20), `withdraw` (109), `transfer` (232), `deductForPurchase` (64), `purchaseCourse` (1062), `refundWithdrawalToWallet` (731), `approveWithdrawal` (658), `rejectWithdrawal` (694), `markWithdrawalPaid` (535), `markWithdrawalFailed` (583), `viewWithdrawal` (486), `cancelWithdrawal` (505).
- Every mutation runs inside `DB::transaction` and writes a paired `WalletTransaction` row with `balance_before`/`balance_after` (double-entry equivalent for a single-account ledger).
- Idempotency: `withdraw` supports `idempotency_key` (WalletService.php:112-117); `WalletTransaction` has `idempotency_key` column (from `2026_07_12_000001_add_withdrawal_fields`).
- Maker–checker: threshold check at WalletService.php:667-671 (`config('wallet.withdraw.maker_checker_threshold')`).
- Opening-balance baseline: `recordOpeningBalance()` at WalletService.php:830 — inserts a reconciling row so `users.wallet == Σ(balance_after − balance_before)` afterwards. Idempotent.
- Legacy flag: `flagLegacyWithdrawal()` at WalletService.php:887 — marks pre-hardening rows without paired refund. Pattern is already there.
- Refund via ledger: `refundWithdrawalToWallet()` at WalletService.php:731 — every reversal writes a compensating row + audit log.
- Withdraw limits (daily/monthly): `assertWithinWithdrawLimits()` at WalletService.php:770.
- Version column (optimistic lock): every state transition increments `version` — see line 494, 517, 554, 601, 617, 682, 712.
- Audit log: `AuditLogService` wired at every transition (`withdrawal.created`, `withdrawal.viewed`, `withdrawal.approved`, `withdrawal.rejected`, `withdrawal.paid`, `withdrawal.failed`, `withdrawal.refunded`, `withdrawal.flagged_legacy`, `wallet.opening_balance`).
- State machine: `WalletTransaction::canTransitionTo` (used at line 613).

### 1.2 CoursePointAccountService (`api/nuxnanravel/app/Services/CoursePointAccountService.php`)

Course-scoped **point** wallet with ledger + reservation:

- `credit(courseId, lessonId, userId, amount, pointsTxId)` (23-66): `lockForUpdate + firstOrCreate` → update balance → create `CoursePointTransaction` with `balance_before/after`. No outer `DB::transaction` here (caller supplies one from `LessonAccessService`).
- `withdraw(courseId, recipient, amount, performedBy)` (70-139): full `DB::transaction` → lock → check `canWithdraw` → update `balance` + `total_withdrawn` → create ledger row → credit user via `PointsService::earn`.
- `createCampaign` (143-188): lock account, reserve budget when `max_claims` set, create campaign row.
- `createLessonRewardCampaign` (192-247): same pattern; guards duplicate active lesson campaign.
- `grantLessonCompletionReward` (251-348): lock campaign then account (deadlock-safe order), check duplicate claim, decrement balance + reserved, create both `CoursePointTransaction` + `CoursePointCampaignClaim`, credit student via `PointsService`.
- `cancelCampaign` (352-374): releases remaining reserve.
- `reserve` (378) / `releaseReserve` (383): direct increments on `reserved_balance`.

**Gap in this service (Phase 1 must fix):**
- Direct `balance` mutation at line 380 and 385 (`$account->update([...])`) has no matching row in `course_point_transactions` — reservation moves are silent. Balance sum ≠ ledger sum if you look at `reserved_balance` history.
- No idempotency key on any operation. `credit`, `withdraw`, `grantLessonCompletionReward`, `createCampaign` can double-run under retry.
- No version column on `course_point_accounts`.
- `createCampaign` and `createLessonRewardCampaign` firstOrCreate the account inside `lockForUpdate()` — the lock is acquired **after** create, which is not race-safe against a first-ever concurrent write. Small window but real.

### 1.3 Existing donation surface (`api/nuxnanravel/app/Models/Donate.php`, `DonateRecipient.php`, `DonateController.php`, `routes/earn/donate.php`)

- `Donate` is **user-to-user**: fields `donor_id`, `user_id` (recipient), `donor_name`, `amounts`, `slip`, `transfer_date`, `donation_purpose`, `payment_method`, `transaction_id`, `remaining_points`, `status` (int: 0/1/2), `approved_by`, `privacy_settings`, `notes`, `reviewed_at`, `review_note`. Softdeletes.
- `DonateRecipient` is a many-recipient join.
- **No `course_id` field.** Cannot target a course today.
- Admin queue exists: `/plearnd-admin/supports/donates` (index, bulkReview, show, update, destroy, receive, reject). Anonymous public routes: `POST /supports/donates`, widgets, history.
- Status is integer enum, not a state-machine string. No idempotency key. No ledger link (the `receive` action just updates `status`, does not credit anywhere I can see from the controller — needs verification of the `receive` method body which was not read here).

### 1.4 Campaign / Ad delivery

- `CoursePointCampaign` — has `total_budget`, `points_per_claim`, `max_claims`, `total_claimed`, `total_points_claimed`, `status` (ACTIVE/PAUSED/ENDED/DEPLETED), `campaign_type` (LESSON/MANUAL), `lesson_id`, `starts_at`/`ends_at`, `eligible_type`. Reservation is via `CoursePointAccount.reserved_balance`, not a per-campaign column.
- `CoursePointCampaignClaim` — per-user claim record with `points_transaction_id`, `course_point_transaction_id`, `claimed_at`.
- `CampaignDeliveryEvent` — has `idempotency_key` (from `2026_07_12_130000`). Frontend-side heartbeat not yet enforced server-side (needs verification of controller).
- `Services/Campaign/` — has `CampaignPricingService`, `CampaignRefundService`, `CampaignViewService`, `CampaignDeliveryService`, `CampaignAuthorizationService` — full domain layer already split.
- `Advert` has campaign fields backfilled (`2026_07_12_120000` + `_120001`).

### 1.5 Other supporting infrastructure

- `WalletReconciliationService.php` exists (not read this pass; needs check).
- `AuditLogService` is wired everywhere (used at 209, 496, 499, 522, 568, 603, 618, 683, 714, 756, 870, 909).
- `AdminWalletService`, `AdminPointsService`, `PointsService`, `Gamification\ClassroomPointsService` — existing services indicate mature domain layer.

## 2. Balance-write hot list (files that need attention when introducing a new Ledger layer)

- `app/Services/CoursePointAccountService.php` — lines 48, 95, 294, 301 (balance mutations); 380, 385 (reserved_balance mutations without ledger row).
- `app/Services/LessonAccessService.php` — mentioned in grep hit; caller of `CoursePointAccountService::credit`; needs a read to confirm it wraps in a transaction.
- `app/Services/LessonCompletionService.php` — grep hit; likely caller of `grantLessonCompletionReward`.
- `app/Http/Controllers/Api/Learn/Course/lessons/LessonProgressController.php`, `Api/Learn/Course/points/CoursePointCampaignController.php`, `Api/Learn/Course/points/LessonRewardCampaignController.php`, `Api/Learn/Course/points/CoursePointAccountController.php` — controllers to check for any direct model mutation outside the service.
- `app/Http/Resources/Learn/Course/lessons/LessonResource.php` — read-only, ignore.

## 3. Routes inventory

- `routes/earn/donate.php` — user-to-user donation surface (see 1.3). **No course-scoped endpoint.**
- `routes/earn/points-wallet.php` — read to enumerate.
- `routes/earn/advert.php` — advert sponsorship surface.
- `routes/earn/campaign.php` — advert-campaign surface.
- `routes/learn/course.php` — need to grep for point/wallet endpoints already exposed per course.
- `routes/admin/admin.php` — admin queues.

_(Detailed route dump: run `php artisan route:list --path=donate|wallet|campaign|advert|point` to enumerate before Phase 1 starts.)_

## 4. Frontend touchpoints

- `ui/components/widgets/advertises/AdViewerModal.vue` — existing ad viewer with countdown. Not audited this pass; must be re-read to see how it reports completion (is it Frontend-Timer-Only or does it hit a server heartbeat endpoint?).
- Any `ui/pages/**` referring to Donate / support widget — needs grep. Widget endpoint already exists at `/donates/widget`.

## 5. Data-snapshot SQL (do not run in this session — hand to a human)

```sql
-- (a) Every course_point_account: does balance == credits − debits from its ledger?
SELECT a.id AS account_id, a.course_id, a.balance AS stored_balance,
       COALESCE(SUM(t.balance_after - t.balance_before), 0) AS ledger_delta_sum,
       a.balance - COALESCE(SUM(t.balance_after - t.balance_before), 0) AS mismatch
FROM course_point_accounts a
LEFT JOIN course_point_transactions t ON t.course_point_account_id = a.id
GROUP BY a.id, a.course_id, a.balance
HAVING mismatch <> 0;

-- (b) Active campaigns with unspent budget
SELECT c.id, c.course_id, c.title, c.status, c.total_budget, c.total_points_claimed,
       (c.max_claims - c.total_claimed) * c.points_per_claim AS reserved_remaining
FROM course_point_campaigns c
WHERE c.status IN ('active','paused') AND c.max_claims IS NOT NULL;

-- (c) Accounts with reserved_balance > sum(active campaigns' reserve_remaining)  — orphan reservation
SELECT a.id, a.course_id, a.reserved_balance,
       COALESCE(SUM((c.max_claims - c.total_claimed) * c.points_per_claim), 0) AS expected_reserved,
       a.reserved_balance - COALESCE(SUM((c.max_claims - c.total_claimed) * c.points_per_claim), 0) AS orphan
FROM course_point_accounts a
LEFT JOIN course_point_campaigns c
  ON c.course_point_account_id = a.id
 AND c.status IN ('active','paused')
 AND c.max_claims IS NOT NULL
GROUP BY a.id, a.course_id, a.reserved_balance
HAVING orphan <> 0;

-- (d) User wallets: baseline invariant
SELECT u.id,
       u.wallet AS stored_wallet,
       COALESCE(SUM(w.balance_after - w.balance_before), 0) AS ledger_sum,
       u.wallet - COALESCE(SUM(w.balance_after - w.balance_before), 0) AS mismatch
FROM users u
LEFT JOIN wallet_transactions w ON w.user_id = u.id
GROUP BY u.id, u.wallet
HAVING mismatch <> 0;
```

Run (a) and (d) **before** any code change — if either returns rows, `recordOpeningBalance()` (users) and an equivalent `recordCoursePointOpeningBalance()` (courses — new) must be run before Phase 1 to bring ledger and balance into agreement.

## 6. Revised gap list vs. Slim MVP goals

Original plan expected to build: wallets table, ledger_transactions, ledger_entries, LedgerService, reconciler, opening-balance baseline, course wallet split, donation table, donation modal, campaign reservation refactor, campaign scheduler.

Actual gaps after audit:

| Original planned deliverable | Actually needed? | Reason |
|---|---|---|
| New polymorphic `wallets` table | **No** — user cash lives in `users.wallet` + `wallet_transactions`; course points live in `course_point_accounts` + `course_point_transactions`. Both are already ledger-backed. | Building a third abstraction on top would rewrite working code for no invariant win. |
| `ledger_transactions` / `ledger_entries` tables | **No, not now.** Both existing wallets already have `balance_before/after` on their per-domain transaction table. That's equivalent to a single-account ledger. | Introduce a unified ledger only when we need multi-account entries (Ad revenue split → student + course + platform in one transaction). Slim MVP does not need that. |
| `LedgerService` | **Partial.** Instead: consolidate reservation into `CoursePointAccountService` by writing `course_point_transactions` rows for reserve/release (fix the silent mutation). | The service already exists; we harden it. |
| Course-cash wallet | **Yes.** Courses have no cash wallet today. Deferring: not needed until Ad revenue split lands. **Out of Slim MVP.** | Advert already tracks its own budget/spent on `adverts` table (`2026_07_12_120000_add_campaign_fields`). |
| `course_donations` table | **Compromise.** Extend `donates` with `course_id` (nullable FK) + `donation_type` enum (`user`, `course_point`, `course_cash`) OR create sibling table `course_donates`. See §7-Q1. |
| Donation service with idempotency | **Yes.** `Donate` today has no idempotency, no state machine, `status` is int. Need service methods that credit `CoursePointAccount` when approved. |
| Frontend donation modal targeted at course | **Yes.** New. |
| Campaign budget reservation refactor | **Partial** — reservation mostly works, but reserve/release writes are silent (no ledger row). Add `course_point_transactions` entries with type `campaign_reserve` / `campaign_release`. |
| Orphan-reservation scheduler | **Yes.** Small cron. |
| Feature flag rollout | **Yes.** Small config. |
| Payment gateway, slip verification, withdrawal maker-checker | **Not in Slim MVP.** Withdrawal maker-checker is already implemented at `WalletService.php:667-671`. Gateway is a separate multi-week effort deferred. |

## 7. Risks & open questions (need human decision before Phase 1 code)

**Q1 — Donation storage decision:** Extend `donates` (add `course_id` nullable + `donation_type` enum) OR create a sibling `course_donates` table?
- Extend: less code, admin queue reuses.
- Sibling: cleaner separation, no risk of breaking user-to-user donation flow.

**Q2 — Point-donation debit source:** User has `users.pp` (personal points) — is that where donation-point comes from? Or does donor need a "course-support wallet" separate from `pp`? Confirm current `PointsService::earn`/deduct semantics before wiring.

**Q3 — Anonymous donor while keeping ledger truth:** `Donate.privacy_settings` exists but semantics unclear — read schema to confirm what values it stores. Ledger must always record `donor_id` even when public display is anonymous.

**Q4 — Campaign reservation ledger backfill:** Every currently-non-zero `course_point_accounts.reserved_balance` has no corresponding `course_point_transactions` row. If we add `campaign_reserve` type going forward, do we backfill retroactively (creating one virtual row per active campaign to match the reserved sum) or skip? Recommend: yes, one `campaign_reserve_opening` row per account with non-zero reserved.

**Q5 — Donation-approves-auto-credit semantics:** Point donation should credit immediately (donor has the points now, no risk). Cash/slip donation must NOT credit `course_point_accounts.balance` until admin approves. The `receive` action in DonateController is the trigger. Confirm the current `receive` method — my read cut off before seeing it.

**Q6 — Idempotency-Key contract for `POST /supports/donates`:** New donation-creation should require an `Idempotency-Key` header (UUIDv4). Server stores for 24h. Return 200 with the prior response on replay. Confirm this is acceptable API contract.

**Q7 — Feature flag scope:** `platform_settings.course_donation_enabled` — is this a global switch, per-academy, or per-course? Recommend per-course opt-in with a global kill switch.

**Q8 — LessonAccessService caller of `credit`:** Not read yet. If `credit` is called outside a DB transaction, Phase 1a must wrap it — otherwise a network hiccup between `update balance` and `create transaction` breaks the invariant.

## 8. Revised Phase 1 sub-task breakdown (Slim MVP, ~3-4 weeks)

**Phase 1a — Harden CoursePointAccountService (1-2 days)**
- Files: `app/Services/CoursePointAccountService.php`, tests under `tests/Feature/`.
- Add `course_point_transactions` rows for reservation moves (types: `campaign_reserve`, `campaign_release`). Wrap `reserve`/`releaseReserve` in the caller's transaction context.
- Add idempotency_key column to `course_point_transactions` (migration; nullable, unique). Existing rows null.
- Add `version` column to `course_point_accounts` (migration; default 0, increment on every mutation).
- Fix firstOrCreate-outside-lock race (createCampaign / createLessonRewardCampaign): use explicit `INSERT ... ON DUPLICATE KEY UPDATE` or split into `first` + `create-if-null` inside a serialized txn.
- Tests: concurrent reserve + release, idempotent double-credit, opening-balance-style baseline for `course_point_accounts` (mirror `WalletService::recordOpeningBalance`).
- DoD: new invariant SQL query (from §5 (a)) returns 0 rows after backfill; concurrency test 100× parallel `credit` passes.

**Phase 1b — Add course-scoped donation surface (2-3 days)**
- Migration: add `course_id` nullable FK to `donates`; add `donation_type` enum default `user`; add `idempotency_key` nullable unique; add `version` default 0.
- Extend `Donate` model with the new fields and a `course()` relationship.
- New service method: `DonateService::createCoursePointDonation(donor, course, amount, idempotencyKey)` — locks donor pp + `CoursePointAccount`, debits pp via `PointsService`, credits `CoursePointAccount` via `CoursePointAccountService::credit` variant that accepts a donation source, creates `Donate` row with status=1 (immediately approved because points can't bounce), writes `course_point_transactions` typed `donation_credit`.
- Extend admin `receive` action for cash/slip donations targeted at courses: when approving, credit `CoursePointAccount` (via new method with source=`donation_cash`).
- Endpoint: `POST /api/courses/{course}/donations/points` (auth), `POST /api/courses/{course}/donations/cash` (auth, slip upload).
- Policy: donor cannot be course owner.
- Tests: happy path, self-donation blocked, idempotency replay, insufficient pp.
- DoD: donation to a course visibly moves points, admin queue shows it, replay returns prior response.

**Phase 1c — Frontend donation modal + course support surface (3-4 days)**
- New: `ui/components/donation/CourseDonationModal.vue` (wizard: amount → purpose → anonymous → review).
- Add "สนับสนุนแต้ม" button on public course page (identify: `ui/pages/courses/[slug].vue` or academy course-detail page).
- Owner view of received donations under course wallet page (PII-masked for donor email; use existing admin donation resource for full detail).
- Contract: attach `Idempotency-Key` header (UUIDv4) generated in composable.
- Tests: Cypress-style or component tests where available.
- DoD: manual QA a full donation from a second account.

**Phase 1d — Feature flag + rollout (1 day)**
- Config: `config/platform.php` → `course_donation.enabled` (global) + per-course boolean in `courses.donation_enabled` (nullable, default true when global on).
- Middleware or policy guard on new endpoints.
- Backfill script to open the flag on 1 pilot course.

**Phase 1e — Optional: reservation ledger backfill + orphan cron (0.5 day)**
- `php artisan course-points:reconcile-reservations` — for every account with non-zero reserved, insert one `campaign_reserve_opening` row equal to the sum of expected reserves from live campaigns; the difference (if any) is the orphan and gets logged for a human to inspect (not auto-fixed).
- Scheduled cron every 5 min: cancel campaigns past `ends_at` and release reservation.

**Total: ~1.5 to 2 developer weeks for a single engineer, plus code review and QA.**

## 8.5. Answers to Q1 and Q5 (2026-07-18, after further code read)

### Q5 answer — `DonateController::receive` (line 241-260)

`receive` **does NOT credit any balance**. It only flips `status: 0 → 1` and stamps `approved_by` + `reviewed_at`. No `DB::transaction`, no lock, no ledger row, no user or course crediting.

The crediting happens elsewhere: `DonateController::getDonate` (starts at line 285) checks `remaining_points >= 270`, applies a rate-limit (10 requests/user/day/donate), then decrements `remaining_points` by 270 and credits the caller with 270 points. So the existing `Donate` model is a **public 270-point giveaway pool**, not a direct donor→recipient transfer:

- Donor creates a pool with `amounts` points (`remaining_points = amounts`, `status = 0`)
- Admin approves (`status = 1`)
- Any authenticated user calls `getDonate` → gets 270 points → pool's `remaining_points -= 270`
- `DonateRecipient` is the history of who has collected from the pool
- `Donate.user_id` is not "the recipient" — likely the announcer/owner of the pool

**Semantics are completely different from a course-targeted donation.** A donor who wants to support a specific course wants their points to (a) leave their own wallet and (b) land in that course's `CoursePointAccount` — not become a 270-point-per-caller public pool.

### Q1 answer — Build a **separate** `course_donates` table (do NOT extend `donates`)

Reasons:

1. **Semantic mismatch.** `donates` is a public giveaway pool; a course donation is a targeted transfer. Sharing the schema conflates two products.
2. **Approval flow is different.** Point donation to a course should auto-credit `CoursePointAccount` immediately (points already left donor's wallet). Cash/slip donation to a course requires admin verification before credit. `donates.receive` does neither.
3. **Rate-limit and consumption model differ.** `donates` has per-caller rate limits and 270-point chunks. Course donation has no per-user consumption — the donor decides the amount once.
4. **Admin queue is different.** Admin needs to see "who donated how much to which course" — a course-scoped list, not a per-user-pool list. Reusing the same admin queue would clutter both.
5. **Migration risk.** Adding `course_id` + `donation_type` nullable to `donates` invites bugs where the two flows are conflated (e.g. the giveaway code path accidentally sees a course donation row and tries to hand it out as a 270-point pool).

Proposed schema (Phase 1b migration):

```
course_donates
├── id                          bigint pk
├── course_id                   fk → courses.id  (indexed)
├── donor_id                    fk → users.id, nullable when anonymous public entry
├── donor_display_name          string, nullable (what to show on the public UI)
├── donation_type               enum('point','cash')  — Slim MVP uses only 'point'
├── amount                      integer (points) or decimal(18,4) — one column per type
├── points_amount               bigint, nullable  — set when donation_type='point'
├── cash_amount                 decimal(18,4), nullable — set when donation_type='cash'
├── currency                    string(3), default 'THB'
├── status                      enum('pending','approved','rejected','refunded','completed')
├── purpose                     text, nullable
├── anonymous                   boolean, default false  (hides donor from public UI, not from ledger)
├── slip_path                   string, nullable  — cash type only
├── payment_method              string, nullable  — cash type only
├── payment_reference           string, nullable
├── idempotency_key             string, nullable, UNIQUE
├── version                     unsigned int, default 0
├── course_point_transaction_id fk → course_point_transactions.id, nullable (set on credit)
├── approved_by                 fk → users.id, nullable
├── reviewed_at                 timestamp, nullable
├── rejection_reason            text, nullable
├── metadata                    json, nullable
├── created_at / updated_at / deleted_at (softdelete)
```

Index: `(course_id, status, created_at)` for admin queue, `idempotency_key` UNIQUE, `donor_id` for donor history.

Point donation status flow: `pending → completed` (immediate on `POST /donations/points`).
Cash donation status flow: `pending → approved → completed` (approve = admin verifies slip; completed = ledger row written).

### Bonus finding — `Donate` model has no `DB::transaction` or lock anywhere in receive/reject/getDonate

The public 270-point pool flow is **not** race-safe today. Two simultaneous `getDonate` calls on the same pool can both decrement `remaining_points` past zero. Out of Slim MVP scope but worth flagging as a follow-up.

## 9. Recommendations to the human before we start writing code

1. Answer Q1–Q8. Q1 (extend vs sibling) and Q5 (receive semantics) are the two that shape the migration.
2. Run the SQL from §5 against production DB and report results. If (a) or (d) return rows, we need opening-balance baseline runs before any refactor.
3. Confirm the Slim MVP scope drops (a) cash-gateway integration, (b) new polymorphic wallet table, (c) generic ledger tables. All three were in the original plan and are now recommended dropped as premature.
4. Confirm that "revenue routes to Academy Wallet" is a Phase-2-later concern, not Slim MVP. In this MVP donations flow into the existing `course_point_accounts`, not into any new Academy Wallet.

## 10. SQL invariant results (executed 2026-07-18 against local DB `nuxnan_nuxnan_db`)

Ran §5 queries (a) and (d) via `php artisan tinker`. Results:

- **§5(a) — `course_point_accounts` vs `course_point_transactions`: `0 mismatches`.** Ledger integrity is perfect on every course wallet. **Phase 1a's opening-balance backfill for courses is unnecessary.** The `recordCoursePointOpeningBalance()` mirror service is not needed.
- **§5(d) — `users.wallet` vs `wallet_transactions`: 2 mismatches:**
  - `user=117 stored=3.00 ledger=0.00 diff=3.00`
  - `user=412 stored=1.75 ledger=0.00 diff=1.75`

These 2 user rows are a pre-existing hardening gap unrelated to Course Donation. Recommend a one-shot maintenance job: for these two users, call `WalletService::recordOpeningBalance($user)` (already implemented at line 830) to insert the reconciling row. Independent of Slim MVP scope.

Queries (b) and (c) were not run — they inform later phases (campaign reservation ledger backfill) that Phase 1a can now defer.

## 11. Refined Phase 1 (post-audit, post-answers)

Drop:
- ~~Wallet Ledger Foundation from the old 12-phase plan~~ — the ledger discipline exists.
- ~~Opening-balance mirror for courses~~ — already balanced.
- ~~Extending `donates` with course_id~~ — Q1 answered: build sibling table.

Confirmed Phase 1 backlog (Slim MVP):

- **1a — Harden `CoursePointAccountService`** (1-2 days)
  - Add ledger rows for `reserve`/`releaseReserve` (types `campaign_reserve`, `campaign_release`).
  - Add `idempotency_key` UNIQUE column to `course_point_transactions`.
  - Add `version` unsigned int to `course_point_accounts`.
  - Fix firstOrCreate-outside-lock race in `createCampaign` / `createLessonRewardCampaign`.
  - Wrap `credit()` in `DB::transaction` if caller does not (verify `LessonAccessService`).

- **1b — `course_donates` sibling table + service + endpoints** (2-3 days)
  - Migration `create_course_donates_table` per schema in §8.5 Q1.
  - `CourseDonateService::createPointDonation` — deducts donor `users.pp` via `PointsService`, credits `CoursePointAccount` via a new `CoursePointAccountService::creditFromDonation()` method that writes a `course_point_transactions` row with `type=donation_credit`, creates the `course_donates` row `status=completed`.
  - `CourseDonateService::createCashDonation` — creates `pending` row + slip upload; a separate `approve()` credits the account in the same way with `type=donation_cash_credit`.
  - Policy: donor ≠ course owner; donation module feature-flag guard.
  - Endpoints: `POST /api/courses/{course}/donations/points` (auth), `POST /api/courses/{course}/donations/cash` (auth, upload), `GET /api/me/course-donations`, admin queue `GET /api/plearnd-admin/course-donations` + `PATCH ../{id}/approve` + `PATCH ../{id}/reject`.
  - Idempotency-Key header contract (24h retention).

- **1c — Frontend** (3-4 days)
  - `ui/components/donation/CourseDonationModal.vue` wizard.
  - Public course page — support button on `ui/pages/courses/[slug].vue` (verify slug route or use existing detail page).
  - `ui/pages/me/course-donations.vue` — donor history.
  - Course-owner readonly view under existing wallet/campaigns page (PII-masked donor email).

- **1d — Feature flag** (1 day)
  - `config/platform.php` → `course_donation.enabled` (global).
  - `courses.donation_enabled` boolean column (nullable, default null = inherit global).
  - Middleware guard.

- **1e — Optional cleanups** (0.5 day)
  - One-shot fix for users 117 + 412 via `recordOpeningBalance`.
  - Reservation orphan cron (every 5 min, close expired campaigns + release reserve).

Estimate confirmed: **1.5 to 2 developer weeks** for Phase 1a-1d. 1e is deferrable.

## 12. Phase 5 audit (2026-07-18, after Phase 1 completion)

### 12.1 What exists for Ad delivery + reward

- `Advert` model (`app/Models/Advert.php`) — fused ad+campaign. Enums `CampaignType`, `CampaignScopeType`, `CampaignPaymentStatus`, `CampaignReviewStatus`. Fields include `budget_amount`, `remaining_views`, `duration`, `review_status`, `impressions_count`, `academy_id`, `course_id`, `advertiser_id`, `beneficiary_id`.
- `AdvertViewer` — per-viewer log with `idempotency_key`.
- `CampaignDeliveryEvent` (`app/Models/CampaignDeliveryEvent.php`) — has `event_type`, `ip_hash`, `user_agent`, `placement`, `idempotency_key`, `metadata`. No timestamps on update. **Missing** for Phase 5b: `session_id`, `delivery_token_hash`, `started_at`, `last_heartbeat_at`, `completed_at`, `required_duration`, `page_visibility_ratio`, `device_fingerprint_hash`, `fraud_reason`, `status`.
- `Services/Campaign/CampaignViewService::rewardedView(Advert, User, string idempotencyKey)` — already has: lockForUpdate on Advert + User, idempotency check via `advertViewers`, daily view limit (config `campaign.daily_views_per_user`, default 5), pp deduction (`duration * 20`), wallet reward for viewer (`duration * viewer_reward_per_second + pointsRequired/pointsPerBaht`), referrer wallet reward (`duration * referrer_reward_per_second`), fallback to platform account for referrer (`config('campaign.platform_account_code')`).
- `CampaignController::view` (line 156) — thin wrapper that calls `rewardedView`.
- `Services/Campaign/CampaignPricingService`, `CampaignRefundService`, `CampaignAuthorizationService`, `CampaignDeliveryService`, `SupportPaymentService` — full domain layer already split.

### 12.2 Gaps for Phase 5b (hardening)

- Client trust: `POST /adverts/{campaign}/view` accepts a claim that user watched, no server-side duration proof. Needs `start` + `heartbeat` + `complete` endpoints.
- No token issuance for delivery session — replay/reuse cannot be detected beyond the idempotency_key check.
- No page-visibility ratio; a user with the tab hidden gets full reward.
- No fraud scoring; `FraudDetectionService` does not exist yet.
- `Advert.remaining_views` decrement happens at the *view* call — should move to *complete* call so a started-but-never-completed view does not consume a slot.

### 12.3 Gaps for Phase 5c (revenue split refactor)

- `rewardedView` computes viewer/referrer rewards from config, not from `RevenueSharePolicyResolver` (Phase 5a).
- Course share is ZERO today. `Advert.course_id` is set but `CoursePointAccount` is never credited.
- Platform share is implicit (whatever isn't distributed stays in `budget_amount`). Should be explicit via `CoursePointAccount.platform_earned` (Phase 5a helper) or a dedicated Platform Wallet.
- Reward split is coupled to `duration` and `pointsRequired`, not to a `gross_reward` amount and a policy. Refactor: compute `gross = duration * gross_reward_per_second`, then `split = resolver->split(gross, policy)`, credit each leg accordingly. Existing `viewer_reward_per_second` becomes derived from student_pct of the policy.
- No policy_version stored in metadata, so historical audit of "what split was applied to this view" is impossible.

### 12.4 What Phase 5b/5c/5d must NOT break

- Existing donor pp deduction and referrer wallet flow — must remain but be re-expressed through the policy.
- Existing `advertViewers` idempotency semantic — new session/token flow is layered on top; the underlying view still de-duplicates by `(user_id, advert_id, idempotency_key)`.
- Ad selection / query (`CampaignDeliveryService::query`) — untouched.
- `campaign.daily_views_per_user` config guard — moves from `view` to `start` (must not allow starting more than N sessions/day).

### 12.5 Recommended Phase 5 sub-task split (post-audit)

- **5a — Foundation** (already in Task #20 to Codex): `revenue_share_policies` table, resolver, split calculator, `CoursePointAccount.platform_earned` helper. Foundation-only, no touching ad flow.
- **5b — Delivery hardening** (Task #21): Widen `campaign_delivery_events` with new columns; new endpoints `POST /adverts/{advert}/deliveries/start`, `POST /deliveries/{delivery}/heartbeat`, `POST /deliveries/{delivery}/complete`. Signed JWT delivery token (short TTL, single-use). Server-side duration = last_heartbeat_at - started_at. Fraud rules: token replay, missing heartbeat, low visibility ratio, IP/device velocity. Move `remaining_views` decrement from view→complete.
- **5c — Reward distribution refactor** (Task #22): Replace hard-coded reward math in `CampaignViewService::rewardedView` (or its `complete`-time successor) with `RevenueSharePolicyResolver::resolve` + `split`. Credit viewer via `PointsService::earn` (or `wallet` increment for the wallet share, TBD), credit course via `CoursePointAccountService::creditFromDonation(...) with new type `TYPE_AD_REVENUE`, credit platform via `CoursePointAccount::incrementPlatformEarned` per-course. Store `policy_version` and `policy_id` in `campaign_delivery_events.metadata`. All in one DB::transaction, double-entry invariant test.
- **5d — Frontend `AdViewerModal.vue`** (Task #23): Show reward preview fetched from server (never compute). Heartbeat every 5s. Progress bar from server duration. Sponsor + course beneficiary info. Handle token replay 409.
- **5e — Admin policy management UI** (Task #24): Super Admin CRUD on `revenue_share_policies` (platform/academy/course/campaign scope). Effective dates + version history. "Which policy was used for transaction X" report.

## 13. Phase 9/10/11 audit (2026-07-18, post Phase 5)

### 13.1 Phase 9 — Course-level withdrawal maker-checker

- `CoursePointAccountService::withdraw($courseId, $recipient, $amount, $performedBy)` is a **one-shot immediate** withdraw: lock account, check `canWithdraw`, decrement balance, create `CoursePointTransaction` type=`owner_withdraw`, and immediately credit recipient's `pp` via `PointsService::earn`. No pending state, no approval flow, no maker/checker.
- No `course_point_withdrawal_requests` table exists.
- User-level withdrawal via `WalletService` **does** have maker/checker (Phase 0 audit §1.1). Pattern to mimic: `viewWithdrawal → approveWithdrawal → processWithdrawal → markWithdrawalPaid` with `reviewed_by ≠ approved_by` guard at high-value threshold.
- Existing `CoursePointAccountService::withdraw` must NOT be broken — course owners with low balances doing small self-withdrawals to their own `pp` should still work.
- New flow: request → academy admin review → super admin approve → paid (with proof upload). Above a config threshold, require different approver from creator/reviewer.

### 13.2 Phase 10 — Fraud detection + reconciliation

- `WalletReconciliationService` exists (`app/Services/WalletReconciliationService.php` — not yet read; assume it verifies `users.wallet` vs `sum(wallet_transactions delta)` since `WalletService::recordOpeningBalance` follows same invariant).
- No `FraudDetectionService`.
- No `risk_events` table.
- `AuditLogService` is wired for withdrawal events (Phase 0 §1.1).
- Existing ad delivery hardening (Phase 5b) has `fraud_reason` and `status` in `campaign_delivery_events` — can populate risk events from these.

### 13.3 Phase 11 — Public discovery

- `CourseMarketplaceController` exists at `app/Http/Controllers/Api/Learn/Course/CourseMarketplaceController.php`; route `GET /api/courses/marketplace` returns list. Not audited for donation-support surfacing yet — likely does NOT expose `donation_enabled`, `total_donated`, or campaign progress.
- No `ui/pages/marketplace/` directory. Public course listing may be part of `ui/pages/Learn/*` or academies; needs grep before writing new page.
- No public `/courses/{slug}` route in Nuxt. Course detail today likely lives at `ui/pages/Learn/Courses/[id].vue` (auth-required).

### 13.4 Sub-task split for Phase 9/10/11

- **9a** — `course_point_withdrawal_requests` migration + model + `CoursePointWithdrawalService` (request/review/approve/reject/mark-paid with maker-checker) + tests.
- **9b** — Controllers + policy + admin queue endpoints (mimics donate admin queue).
- **9c** — Frontend: course owner "ขอถอนแต้ม" form + admin approval page.
- **10a** — `risk_events` table + `FraudDetectionService` (rule-based scoring: velocity, self-donation cluster, ad fraud reason).
- **10b** — Daily `reconcile:all` command + alert + admin risk queue page.
- **11a** — Public `/api/public/courses` + `/api/public/courses/{course}/support-summary` + `CourseMarketplaceResource` extension with donation signals.
- **11b** — Public frontend `ui/pages/courses/index.vue` listing + course detail with donation flow entry.

## 14. Phase 12 audit — Academy (school) donation, Slim scope (2026-07-18)

### 14.1 What exists

- `Academy` model with settings, roles, members, admins.
- `AcademyPointRule` — rules for point reward within an academy (not a wallet).
- Course.academy_id — hierarchy is Academy → Course.
- Existing course-donation infrastructure (Phase 1b) uses a sibling `course_donates` table pattern.

### 14.2 What does NOT exist

- No `academy_point_accounts`, `academy_donates`, or academy-level wallet.
- No academy-scoped donation UI or admin queue.
- No allocation flow from Academy → Course.
- No public academy discovery page (Phase 11 covers only courses).

### 14.3 Slim scope decision (per user, 2026-07-18)

Build donor → Academy direct donation with allocation-down-to-course support. Defer:
- Ad revenue routing to Academy Wallet (was audit §13.2 note; keep in Course wallet for now).
- Academy-level campaigns (all-course promos).
- Academy-level withdrawal maker-checker.

### 14.4 Sub-task split

- **12a** — `academy_point_accounts` migration + `academy_donates` migration + models + AcademyDonateService (point + cash paths, feature-flag guard). Tests.
- **12b** — Controllers + FormRequests + Resource + routes + admin queue + policy.
- **12c** — AcademyAllocationService — academy admin transfers points from academy pool into a course_point_account under the same academy (with ledger rows both sides).
- **12d** — Frontend: AcademyDonationModal (mirror CourseDonationModal), academy admin allocation UI, my/academy-donations history.
- **12e** — Public schools discovery: `/api/public/schools` list + `/api/public/schools/{academy}` detail with support-summary + Nuxt `/schools` and `/schools/[slug]` pages.

## Appendix — Codex delegation failure

For future reference: the Codex-companion `task-mrpl1unp-xbu4v2` was spawned via the `codex:codex-rescue` agent. Log at `~/.claude/plugins/data/codex-inline/state/nuxnan-13289e662c060836/jobs/task-mrpl1unp-xbu4v2.log` shows the task started, ran ~4 PowerShell scans in the first 2 minutes, produced one assistant message ("audit context present at line 3621"), then went silent. Process PID 37356 disappeared without emitting a stop event, so companion status kept reporting `running` for 48 minutes. Recovery: Claude did the audit inline. If we retry Codex on later phases, poll `codex-companion status --json` every ~10 min and treat any `updatedAt` gap over 5 min as a hang.
