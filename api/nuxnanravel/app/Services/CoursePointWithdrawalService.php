<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CoursePointAccount;
use App\Models\CoursePointTransaction;
use App\Models\CoursePointWithdrawalRequest;
use App\Models\User;
use DomainException;
use Illuminate\Database\DatabaseManager;

class CoursePointWithdrawalService
{
    public function __construct(
        protected CoursePointAccountService $accounts,
        protected PointsService $points,
        protected DatabaseManager $db,
        protected AuditLogService $audit,
    ) {}

    public function request(User $requester, Course $course, int $amount, ?string $purpose, ?string $idempotencyKey): CoursePointWithdrawalRequest
    {
        if ($course->user_id !== $requester->id && ! $course->isAdmin($requester)) {
            throw new DomainException('Not authorized to request withdrawal.');
        }
        if ($idempotencyKey && ($existing = CoursePointWithdrawalRequest::where('idempotency_key', $idempotencyKey)->first())) {
            return $existing;
        }
        if ($amount < CoursePointAccount::MINIMUM_WITHDRAWAL) {
            throw new DomainException('Withdrawal is below the minimum.');
        }

        return $this->db->transaction(function () use ($requester, $course, $amount, $purpose, $idempotencyKey) {
            $account = CoursePointAccount::where('course_id', $course->id)->lockForUpdate()->firstOrFail();
            if (! $account->canWithdraw($amount)) {
                throw new DomainException('Insufficient available course points.');
            }
            $this->reserve($account, $amount, $requester->id);
            $request = CoursePointWithdrawalRequest::create(['course_id' => $course->id, 'course_point_account_id' => $account->id, 'requested_by' => $requester->id, 'amount' => $amount, 'purpose' => $purpose, 'idempotency_key' => $idempotencyKey, 'status' => CoursePointWithdrawalRequest::STATUS_PENDING]);
            $this->audit->logCreate($request, 'course_withdrawal');

            return $request;
        });
    }

    public function review(CoursePointWithdrawalRequest $request, User $reviewer): CoursePointWithdrawalRequest
    {
        $this->admin($reviewer);
        if ($request->requested_by === $reviewer->id) {
            throw new DomainException('Requester cannot review.');
        }
        if (! $request->canTransitionTo(CoursePointWithdrawalRequest::STATUS_REVIEWING)) {
            throw new DomainException('Invalid transition.');
        }
        $request->update(['status' => CoursePointWithdrawalRequest::STATUS_REVIEWING, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'version' => $request->version + 1]);

        return $request->fresh();
    }

    public function approve(CoursePointWithdrawalRequest $request, User $approver, ?string $note = null): CoursePointWithdrawalRequest
    {
        $this->admin($approver);
        $threshold = (int) config('wallet.course_withdraw.maker_checker_threshold', 5000);
        if ($request->amount > $threshold && ($request->approved_by === $approver->id || $request->reviewed_by === $approver->id || $request->requested_by === $approver->id)) {
            throw new DomainException('A different maker/checker is required.');
        }
        if (! $request->canTransitionTo(CoursePointWithdrawalRequest::STATUS_APPROVED)) {
            throw new DomainException('Invalid transition.');
        }
        $request->update(['status' => CoursePointWithdrawalRequest::STATUS_APPROVED, 'approved_by' => $approver->id, 'approved_at' => now(), 'admin_note' => $note, 'version' => $request->version + 1]);

        return $request->fresh();
    }

    public function reject(CoursePointWithdrawalRequest $request, User $reviewer, string $reason): CoursePointWithdrawalRequest
    {
        $this->admin($reviewer);
        if ($request->requested_by === $reviewer->id || ! $request->canTransitionTo(CoursePointWithdrawalRequest::STATUS_REJECTED)) {
            throw new DomainException('Invalid rejection.');
        }

        return $this->db->transaction(function () use ($request, $reviewer, $reason) {
            $this->release($request, $reviewer->id);
            $request->update(['status' => CoursePointWithdrawalRequest::STATUS_REJECTED, 'rejection_reason' => $reason, 'version' => $request->version + 1]);

            return $request->fresh();
        });
    }

    public function markPaid(CoursePointWithdrawalRequest $request, User $payer, ?string $paymentReference, array $proofData): CoursePointWithdrawalRequest
    {
        $this->admin($payer);
        if (! $this->makerCheckerDisabled() && in_array($payer->id, array_filter([$request->requested_by, $request->reviewed_by, $request->approved_by]), true)) {
            throw new DomainException('Payer must be independent.');
        }
        if (! $request->canTransitionTo(CoursePointWithdrawalRequest::STATUS_PAID)) {
            throw new DomainException('Invalid payment transition.');
        }

        return $this->db->transaction(function () use ($request, $payer, $paymentReference, $proofData) {
            $request = CoursePointWithdrawalRequest::lockForUpdate()->findOrFail($request->id);
            $account = CoursePointAccount::lockForUpdate()->findOrFail($request->course_point_account_id);
            if ($account->reserved_balance < $request->amount) {
                throw new DomainException('Reservation is insufficient.');
            }
            $before = $account->balance;
            $account->update(['balance' => $before - $request->amount, 'reserved_balance' => $account->reserved_balance - $request->amount, 'total_withdrawn' => $account->total_withdrawn + $request->amount, 'version' => $account->version + 1]);
            $tx = CoursePointTransaction::create(['course_point_account_id' => $account->id, 'course_id' => $request->course_id, 'user_id' => $request->requested_by, 'type' => CoursePointTransaction::TYPE_WITHDRAWAL_PAID, 'amount' => $request->amount, 'balance_before' => $before, 'balance_after' => $before - $request->amount, 'created_by' => $payer->id, 'metadata' => ['request_id' => $request->id]]);
            $this->points->earn($request->requester, $request->amount, 'course_withdrawal', $request->course_id, 'Course withdrawal payout', ['course_point_transaction_id' => $tx->id]);
            $request->update(array_merge(['status' => CoursePointWithdrawalRequest::STATUS_PAID, 'paid_by' => $payer->id, 'paid_at' => now(), 'payment_reference' => $paymentReference, 'course_point_transaction_id' => $tx->id, 'version' => $request->version + 1], $proofData));

            return $request->fresh();
        });
    }

    public function cancel(CoursePointWithdrawalRequest $request, User $canceller): CoursePointWithdrawalRequest
    {
        if ($request->requested_by !== $canceller->id || ! $request->canTransitionTo(CoursePointWithdrawalRequest::STATUS_CANCELLED)) {
            throw new DomainException('Cannot cancel this request.');
        }

        return $this->db->transaction(function () use ($request, $canceller) {
            $this->release($request, $canceller->id);
            $request->update(['status' => CoursePointWithdrawalRequest::STATUS_CANCELLED, 'version' => $request->version + 1]);

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
        return (bool) config('wallet.course_withdraw.maker_checker_disabled', false);
    }

    protected function reserve(CoursePointAccount $account, int $amount, int $userId): void
    {
        $before = $account->balance;
        $account->update(['reserved_balance' => $account->reserved_balance + $amount, 'version' => $account->version + 1]);
        $this->ledger($account, $amount, CoursePointTransaction::TYPE_WITHDRAWAL_RESERVE, $before, $userId);
    }

    protected function release(CoursePointWithdrawalRequest $request, int $userId): void
    {
        $account = CoursePointAccount::lockForUpdate()->findOrFail($request->course_point_account_id);
        if ($account->reserved_balance < $request->amount) {
            throw new DomainException('Reservation is insufficient.');
        } $before = $account->balance;
        $account->update(['reserved_balance' => $account->reserved_balance - $request->amount, 'version' => $account->version + 1]);
        $this->ledger($account, $request->amount, CoursePointTransaction::TYPE_WITHDRAWAL_RELEASE, $before, $userId);
    }

    protected function ledger(CoursePointAccount $account, int $amount, string $type, int $balance, int $userId): void
    {
        CoursePointTransaction::create(['course_point_account_id' => $account->id, 'course_id' => $account->course_id, 'user_id' => $userId, 'type' => $type, 'amount' => $amount, 'balance_before' => $balance, 'balance_after' => $balance, 'created_by' => $userId]);
    }
}
