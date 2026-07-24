<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\AcademyPointAccount;
use App\Models\AcademyPointTransaction;
use App\Models\AcademyPointWithdrawalRequest;
use App\Models\User;
use DomainException;
use Illuminate\Database\DatabaseManager;

class AcademyPointWithdrawalService
{
    public function __construct(
        protected AcademyPointAccountService $accounts,
        protected PointsService $points,
        protected DatabaseManager $db,
        protected AuditLogService $audit,
    ) {}

    public function request(User $requester, Academy $Academy, int $amount, ?string $purpose, ?string $idempotencyKey): AcademyPointWithdrawalRequest
    {
        if ($Academy->user_id !== $requester->id && ! $Academy->isAdmin($requester)) {
            throw new DomainException('Not authorized to request withdrawal.');
        }
        if ($idempotencyKey && ($existing = AcademyPointWithdrawalRequest::where('idempotency_key', $idempotencyKey)->first())) {
            return $existing;
        }
        if ($amount < AcademyPointAccount::MINIMUM_WITHDRAWAL) {
            throw new DomainException('Withdrawal is below the minimum.');
        }

        return $this->db->transaction(function () use ($requester, $Academy, $amount, $purpose, $idempotencyKey) {
            $account = AcademyPointAccount::where('academy_id', $Academy->id)->lockForUpdate()->firstOrFail();
            if (! $account->canWithdraw($amount)) {
                throw new DomainException('Insufficient available Academy points.');
            }
            $this->reserve($account, $amount, $requester->id);
            $request = AcademyPointWithdrawalRequest::create(['academy_id' => $Academy->id, 'academy_point_account_id' => $account->id, 'requested_by' => $requester->id, 'amount' => $amount, 'purpose' => $purpose, 'idempotency_key' => $idempotencyKey, 'status' => AcademyPointWithdrawalRequest::STATUS_PENDING]);
            $this->audit->logCreate($request, 'academy_withdrawal');

            return $request;
        });
    }

    public function review(AcademyPointWithdrawalRequest $request, User $reviewer): AcademyPointWithdrawalRequest
    {
        $this->admin($reviewer);
        if ($request->requested_by === $reviewer->id) {
            throw new DomainException('Requester cannot review.');
        }
        if (! $request->canTransitionTo(AcademyPointWithdrawalRequest::STATUS_REVIEWING)) {
            throw new DomainException('Invalid transition.');
        }
        $request->update(['status' => AcademyPointWithdrawalRequest::STATUS_REVIEWING, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'version' => $request->version + 1]);

        return $request->fresh();
    }

    public function approve(AcademyPointWithdrawalRequest $request, User $approver, ?string $note = null): AcademyPointWithdrawalRequest
    {
        $this->admin($approver);
        $threshold = (int) config('wallet.academy_withdraw.maker_checker_threshold', 5000);
        if ($request->amount > $threshold && ($request->approved_by === $approver->id || $request->reviewed_by === $approver->id || $request->requested_by === $approver->id)) {
            throw new DomainException('A different maker/checker is required.');
        }
        if (! $request->canTransitionTo(AcademyPointWithdrawalRequest::STATUS_APPROVED)) {
            throw new DomainException('Invalid transition.');
        }
        $request->update(['status' => AcademyPointWithdrawalRequest::STATUS_APPROVED, 'approved_by' => $approver->id, 'approved_at' => now(), 'admin_note' => $note, 'version' => $request->version + 1]);

        return $request->fresh();
    }

    public function reject(AcademyPointWithdrawalRequest $request, User $reviewer, string $reason): AcademyPointWithdrawalRequest
    {
        $this->admin($reviewer);
        if ($request->requested_by === $reviewer->id || ! $request->canTransitionTo(AcademyPointWithdrawalRequest::STATUS_REJECTED)) {
            throw new DomainException('Invalid rejection.');
        }

        return $this->db->transaction(function () use ($request, $reviewer, $reason) {
            $this->release($request, $reviewer->id);
            $request->update(['status' => AcademyPointWithdrawalRequest::STATUS_REJECTED, 'rejection_reason' => $reason, 'version' => $request->version + 1]);

            return $request->fresh();
        });
    }

    public function markPaid(AcademyPointWithdrawalRequest $request, User $payer, ?string $paymentReference): AcademyPointWithdrawalRequest
    {
        $this->admin($payer);
        if (! $this->makerCheckerDisabled() && in_array($payer->id, array_filter([$request->requested_by, $request->reviewed_by, $request->approved_by]), true)) {
            throw new DomainException('Payer must be independent.');
        }
        if (! $request->canTransitionTo(AcademyPointWithdrawalRequest::STATUS_PAID)) {
            throw new DomainException('Invalid payment transition.');
        }

        return $this->db->transaction(function () use ($request, $payer, $paymentReference) {
            $request = AcademyPointWithdrawalRequest::lockForUpdate()->findOrFail($request->id);
            $account = AcademyPointAccount::lockForUpdate()->findOrFail($request->academy_point_account_id);
            if ($account->reserved_balance < $request->amount) {
                throw new DomainException('Reservation is insufficient.');
            }
            $before = $account->balance;
            $account->update(['balance' => $before - $request->amount, 'reserved_balance' => $account->reserved_balance - $request->amount, 'total_withdrawn' => $account->total_withdrawn + $request->amount, 'version' => $account->version + 1]);
            $tx = AcademyPointTransaction::create(['academy_point_account_id' => $account->id, 'academy_id' => $request->academy_id, 'user_id' => $request->requested_by, 'type' => AcademyPointTransaction::TYPE_WITHDRAWAL_PAID, 'amount' => $request->amount, 'balance_before' => $before, 'balance_after' => $before - $request->amount, 'created_by' => $payer->id, 'metadata' => ['request_id' => $request->id]]);
            $this->points->earn($request->requester, $request->amount, 'academy_withdrawal', $request->academy_id, 'Academy withdrawal payout', ['academy_point_transaction_id' => $tx->id]);
            $request->update(['status' => AcademyPointWithdrawalRequest::STATUS_PAID, 'paid_by' => $payer->id, 'paid_at' => now(), 'payment_reference' => $paymentReference, 'academy_point_transaction_id' => $tx->id, 'version' => $request->version + 1]);

            return $request->fresh();
        });
    }

    public function cancel(AcademyPointWithdrawalRequest $request, User $canceller): AcademyPointWithdrawalRequest
    {
        if ($request->requested_by !== $canceller->id || ! $request->canTransitionTo(AcademyPointWithdrawalRequest::STATUS_CANCELLED)) {
            throw new DomainException('Cannot cancel this request.');
        }

        return $this->db->transaction(function () use ($request, $canceller) {
            $this->release($request, $canceller->id);
            $request->update(['status' => AcademyPointWithdrawalRequest::STATUS_CANCELLED, 'version' => $request->version + 1]);

            return $request->fresh();
        });
    }

    protected function admin(User $user): void
    {
        if (! $user->isPlearndAdmin() && ! $user->isSuperAdmin()) {
            throw new DomainException('Admin access required.');
        }
    }

    protected function makerCheckerDisabled(): bool
    {
        return (bool) config('wallet.academy_withdraw.maker_checker_disabled', false);
    }

    protected function reserve(AcademyPointAccount $account, int $amount, int $userId): void
    {
        $before = $account->balance;
        $account->update(['reserved_balance' => $account->reserved_balance + $amount, 'version' => $account->version + 1]);
        $this->ledger($account, $amount, AcademyPointTransaction::TYPE_WITHDRAWAL_RESERVE, $before, $userId);
    }

    protected function release(AcademyPointWithdrawalRequest $request, int $userId): void
    {
        $account = AcademyPointAccount::lockForUpdate()->findOrFail($request->academy_point_account_id);
        if ($account->reserved_balance < $request->amount) {
            throw new DomainException('Reservation is insufficient.');
        } $before = $account->balance;
        $account->update(['reserved_balance' => $account->reserved_balance - $request->amount, 'version' => $account->version + 1]);
        $this->ledger($account, $request->amount, AcademyPointTransaction::TYPE_WITHDRAWAL_RELEASE, $before, $userId);
    }

    protected function ledger(AcademyPointAccount $account, int $amount, string $type, int $balance, int $userId): void
    {
        AcademyPointTransaction::create(['academy_point_account_id' => $account->id, 'academy_id' => $account->academy_id, 'user_id' => $userId, 'type' => $type, 'amount' => $amount, 'balance_before' => $balance, 'balance_after' => $balance, 'created_by' => $userId]);
    }
}
