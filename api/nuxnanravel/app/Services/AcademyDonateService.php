<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\AcademyDonate;
use App\Models\AcademyPointAccount;
use App\Models\AcademyPointTransaction;
use App\Models\User;
use DomainException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;

class AcademyDonateService
{
    public function __construct(protected PointsService $pointsService, protected DatabaseManager $database) {}

    public function createPointDonation(User $donor, Academy $academy, int $pointsAmount, array $meta, ?string $idempotencyKey): AcademyDonate
    {
        $this->guardEnabled($academy);
        if ($pointsAmount < 1) {
            throw new DomainException('Donation must be at least 1 point.');
        }
        $this->guardOwner($donor, $academy);
        if ($donor->pp < $pointsAmount) {
            throw new DomainException('Insufficient points for this donation.');
        }

        return $this->database->transaction(function () use ($donor, $academy, $pointsAmount, $meta, $idempotencyKey) {
            if ($idempotencyKey && ($old = AcademyDonate::where('idempotency_key', $idempotencyKey)->first())) {
                return $old;
            }
            $spent = $this->pointsService->spend($donor, $pointsAmount, 'academy_donation', $academy->id, 'Academy donation', $meta);
            if (! $spent) {
                throw new DomainException('Insufficient points for this donation.');
            }
            $donation = AcademyDonate::create(['academy_id' => $academy->id, 'donor_id' => $donor->id, 'donor_display_name' => $meta['donor_display_name'] ?? null, 'donation_type' => AcademyDonate::TYPE_POINT, 'points_amount' => $pointsAmount, 'status' => AcademyDonate::STATUS_COMPLETED, 'purpose' => $meta['purpose'] ?? null, 'anonymous' => $meta['anonymous'] ?? false, 'metadata' => $meta, 'idempotency_key' => $idempotencyKey]);
            $tx = $this->credit($academy->id, $donor->id, $pointsAmount, AcademyPointTransaction::TYPE_DONATION_POINT_CREDIT, $idempotencyKey, ['related_points_transaction_id' => $spent->id, 'related_academy_donate_id' => $donation->id] + $meta);
            $donation->update(['academy_point_transaction_id' => $tx->id]);

            return $donation->fresh();
        });
    }

    public function createCashDonation(User $donor, Academy $academy, float $cash, array $meta, ?string $idempotencyKey, ?UploadedFile $slip): AcademyDonate
    {
        $this->guardEnabled($academy);
        if ($cash < 1) {
            throw new DomainException('Cash donation must be at least 1.');
        } $this->guardOwner($donor, $academy);

        return $this->database->transaction(function () use ($donor, $academy, $cash, $meta, $idempotencyKey, $slip) {
            if ($idempotencyKey && ($old = AcademyDonate::where('idempotency_key', $idempotencyKey)->first())) {
                return $old;
            }

            return AcademyDonate::create(['academy_id' => $academy->id, 'donor_id' => $donor->id, 'donor_display_name' => $meta['donor_display_name'] ?? null, 'donation_type' => AcademyDonate::TYPE_CASH, 'cash_amount' => $cash, 'currency' => $meta['currency'] ?? 'THB', 'status' => AcademyDonate::STATUS_PENDING, 'purpose' => $meta['purpose'] ?? null, 'anonymous' => $meta['anonymous'] ?? false, 'slip_path' => $slip?->store('private/academy-donation-slips/'.now()->format('Y/m')), 'payment_method' => $meta['payment_method'] ?? null, 'payment_reference' => $meta['payment_reference'] ?? null, 'metadata' => $meta, 'idempotency_key' => $idempotencyKey]);
        });
    }

    public function approve(AcademyDonate $donation, User $admin, ?string $note): AcademyDonate
    {
        if ($donation->donation_type !== 'cash' || $donation->status !== 'pending') {
            throw new DomainException('Only pending cash donations can be approved.');
        } $this->guardOwner($admin, $donation->academy);

        return $this->database->transaction(function () use ($donation, $admin, $note) {
            $d = AcademyDonate::whereKey($donation->id)->lockForUpdate()->firstOrFail();
            if ($d->status !== 'pending') {
                throw new DomainException('Donation is no longer pending.');
            } $tx = $this->credit($d->academy_id, $d->donor_id, (int) $d->cash_amount, AcademyPointTransaction::TYPE_DONATION_CASH_CREDIT, null, ['note' => $note, 'related_academy_donate_id' => $d->id]);
            $d->update(['status' => 'completed', 'academy_point_transaction_id' => $tx->id, 'approved_by' => $admin->id, 'reviewed_at' => now(), 'version' => $d->version + 1]);

            return $d->fresh();
        });
    }

    public function reject(AcademyDonate $donation, User $admin, string $reason): AcademyDonate
    {
        if ($donation->donation_type !== 'cash' || $donation->status !== 'pending') {
            throw new DomainException('Only pending cash donations can be rejected.');
        }

        return $this->database->transaction(function () use ($donation, $admin, $reason) {
            $d = AcademyDonate::whereKey($donation->id)->lockForUpdate()->firstOrFail();
            if ($d->status !== 'pending') {
                throw new DomainException('Donation is no longer pending.');
            } $d->update(['status' => 'rejected', 'rejection_reason' => $reason, 'approved_by' => $admin->id, 'reviewed_at' => now(), 'version' => $d->version + 1]);

            return $d->fresh();
        });
    }

    protected function credit(int $academyId, ?int $userId, int $amount, string $type, ?string $key, array $metadata): AcademyPointTransaction
    {
        $account = AcademyPointAccount::where('academy_id', $academyId)->lockForUpdate()->first() ?? AcademyPointAccount::create(['academy_id' => $academyId]);
        $before = (int) $account->balance;
        $after = $before + $amount;
        $account->update(['balance' => $after, 'total_earned' => $account->total_earned + $amount, 'version' => $account->version + 1]);

        return AcademyPointTransaction::create(['academy_point_account_id' => $account->id, 'academy_id' => $academyId, 'user_id' => $userId, 'type' => $type, 'amount' => $amount, 'balance_before' => $before, 'balance_after' => $after, 'idempotency_key' => $key, 'metadata' => $metadata]);
    }

    protected function guardEnabled(Academy $academy): void
    {
        if (! $academy->donationEnabled()) {
            throw new DomainException('Donations disabled for this academy.');
        }
    }

    protected function guardOwner(User $user, Academy $academy): void
    {
        if ($user->id === $academy->user_id) {
            throw new DomainException('Academy owners cannot donate to or approve donations for their own academy.');
        }
    }
}
