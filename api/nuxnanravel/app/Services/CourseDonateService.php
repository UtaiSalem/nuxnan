<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseDonate;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CourseDonateService
{
    public function __construct(protected PointLedgerService $ledger) {}

    public function createPointDonation(User $donor, Course $course, int $pointsAmount, array $meta, ?string $key): CourseDonate
    {
        if (! $course->donationEnabled()) {
            throw new DomainException('Donations disabled for this course');
        }
        if ($pointsAmount < 1) {
            throw new DomainException('Donation must be at least 1 point.');
        }
        if ($donor->id === $course->user_id) {
            throw new DomainException('Course owners cannot donate to their own course.');
        }
        if ($donor->pp < $pointsAmount) {
            throw new DomainException('Insufficient points for this donation.');
        }

        return DB::transaction(function () use ($donor, $course, $pointsAmount, $meta, $key) {
            if ($key && ($old = CourseDonate::where('idempotency_key', $key)->first())) {
                return $old;
            }
            $result = $this->ledger->donatePoints($donor, 'course', $course->id, $pointsAmount, 'course_donation', $key, ['reason' => 'course_donation'] + $meta);

            return CourseDonate::create(['course_id' => $course->id, 'donor_id' => $donor->id, 'donor_display_name' => $meta['donor_display_name'] ?? null, 'donation_type' => CourseDonate::TYPE_POINT, 'points_amount' => $pointsAmount, 'status' => CourseDonate::STATUS_COMPLETED, 'purpose' => $meta['purpose'] ?? null, 'anonymous' => $meta['anonymous'] ?? false, 'course_point_transaction_id' => $result['destination_transaction_id'], 'metadata' => $meta, 'idempotency_key' => $key]);
        });
    }

    public function createCashDonation(User $donor, Course $course, float $amount, array $meta, ?string $key, ?UploadedFile $slip): CourseDonate
    {
        if (! $course->donationEnabled()) {
            throw new DomainException('Donations disabled for this course');
        }
        if ($amount < 1) {
            throw new DomainException('Cash donation must be at least 1.');
        }
        if ($donor->id === $course->user_id) {
            throw new DomainException('Course owners cannot donate to their own course.');
        }

        return DB::transaction(function () use ($donor, $course, $amount, $meta, $key, $slip) {
            if ($key && ($old = CourseDonate::where('idempotency_key', $key)->first())) {
                return $old;
            }
            $path = $slip?->store('private/course-donation-slips/'.now()->format('Y/m'));

            return CourseDonate::create(['course_id' => $course->id, 'donor_id' => $donor->id, 'donor_display_name' => $meta['donor_display_name'] ?? null, 'donation_type' => CourseDonate::TYPE_CASH, 'cash_amount' => $amount, 'currency' => $meta['currency'] ?? 'THB', 'status' => CourseDonate::STATUS_PENDING, 'purpose' => $meta['purpose'] ?? null, 'anonymous' => $meta['anonymous'] ?? false, 'slip_path' => $path, 'payment_method' => $meta['payment_method'], 'payment_reference' => $meta['payment_reference'] ?? null, 'metadata' => $meta, 'idempotency_key' => $key]);
        });
    }

    public function approve(CourseDonate $donation, User $admin, ?string $note): CourseDonate
    {
        if ($donation->donation_type !== CourseDonate::TYPE_CASH || $donation->status !== CourseDonate::STATUS_PENDING) {
            throw new DomainException('Only pending cash donations can be approved.');
        }
        if ($admin->id === $donation->course->user_id) {
            throw new DomainException('Course owners cannot approve donations to their own course.');
        }

        return DB::transaction(function () use ($donation, $admin, $note) {
            $d = CourseDonate::whereKey($donation->id)->lockForUpdate()->firstOrFail();
            if ($d->status !== CourseDonate::STATUS_PENDING) {
                throw new DomainException('Donation is no longer pending.');
            }             $tx = $this->ledger->creditCoursePoints($d->course_id, $d->donor_id, (int) $d->cash_amount, null, ['source' => 'donation_cash', 'note' => $note]);
            $d->update(['status' => CourseDonate::STATUS_COMPLETED, 'course_point_transaction_id' => $tx->id, 'approved_by' => $admin->id, 'reviewed_at' => now(), 'version' => $d->version + 1]);

            return $d->fresh();
        });
    }

    public function reject(CourseDonate $donation, User $admin, string $reason): CourseDonate
    {
        if ($donation->donation_type !== CourseDonate::TYPE_CASH || $donation->status !== CourseDonate::STATUS_PENDING) {
            throw new DomainException('Only pending cash donations can be rejected.');
        }

        return DB::transaction(function () use ($donation, $admin, $reason) {
            $d = CourseDonate::whereKey($donation->id)->lockForUpdate()->firstOrFail();
            if ($d->status !== CourseDonate::STATUS_PENDING) {
                throw new DomainException('Donation is no longer pending.');
            }$d->update(['status' => CourseDonate::STATUS_REJECTED, 'rejection_reason' => $reason, 'approved_by' => $admin->id, 'reviewed_at' => now(), 'version' => $d->version + 1]);

            return $d->fresh();
        });
    }
}
