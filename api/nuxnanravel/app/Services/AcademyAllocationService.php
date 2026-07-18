<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\AcademyPointAccount;
use App\Models\AcademyPointTransaction;
use App\Models\Course;
use App\Models\CoursePointAccount;
use App\Models\CoursePointTransaction;
use App\Models\User;
use DomainException;
use Illuminate\Database\DatabaseManager;

class AcademyAllocationService
{
    public function __construct(protected DatabaseManager $database) {}

    public function allocateToCourse(User $admin, Academy $academy, Course $course, int $amount, ?string $purpose, ?string $idempotencyKey): array
    {
        if ($admin->id !== $academy->user_id && ! $academy->isAdmin($admin)) {
            throw new DomainException('Only academy admins can allocate points.');
        }
        if ((int) $course->academy_id !== (int) $academy->id) {
            throw new DomainException('Course does not belong to this academy.');
        }
        if ($amount < 1) {
            throw new DomainException('Allocation amount must be at least 1 point.');
        }
        if ($amount > $academy->pointAccount->available_balance) {
            throw new DomainException('insufficient academy balance');
        }

        if ($idempotencyKey && ($old = AcademyPointTransaction::where('idempotency_key', $idempotencyKey)->first())) {
            return ['academy_tx' => $old, 'course_tx' => CoursePointTransaction::findOrFail($old->related_course_point_transaction_id)];
        }

        return $this->database->transaction(function () use ($admin, $academy, $course, $amount, $purpose, $idempotencyKey) {
            if ($idempotencyKey && ($old = AcademyPointTransaction::where('idempotency_key', $idempotencyKey)->lockForUpdate()->first())) {
                return ['academy_tx' => $old, 'course_tx' => CoursePointTransaction::findOrFail($old->related_course_point_transaction_id)];
            }
            $academyAccount = AcademyPointAccount::where('id', $academy->pointAccount->id)->lockForUpdate()->firstOrFail();
            if ($amount > $academyAccount->available_balance) {
                throw new DomainException('insufficient academy balance');
            }
            $courseAccount = CoursePointAccount::where('course_id', $course->id)->lockForUpdate()->first();
            if (! $courseAccount) {
                $courseAccount = CoursePointAccount::create(['course_id' => $course->id]);
            }
            $academyBefore = (int) $academyAccount->balance;
            $courseBefore = (int) $courseAccount->balance;
            $academyAccount->update(['balance' => $academyBefore - $amount, 'total_distributed' => $academyAccount->total_distributed + $amount, 'version' => $academyAccount->version + 1]);
            $courseAccount->update(['balance' => $courseBefore + $amount, 'total_earned' => $courseAccount->total_earned + $amount, 'version' => $courseAccount->version + 1]);
            $academyTx = AcademyPointTransaction::create(['academy_point_account_id' => $academyAccount->id, 'academy_id' => $academy->id, 'user_id' => $admin->id, 'created_by' => $admin->id, 'type' => AcademyPointTransaction::TYPE_ALLOCATION_OUT, 'amount' => $amount, 'balance_before' => $academyBefore, 'balance_after' => $academyBefore - $amount, 'idempotency_key' => $idempotencyKey, 'metadata' => ['course_id' => $course->id, 'purpose' => $purpose]]);
            $courseTx = CoursePointTransaction::create(['course_point_account_id' => $courseAccount->id, 'course_id' => $course->id, 'user_id' => $admin->id, 'created_by' => $admin->id, 'type' => CoursePointTransaction::TYPE_ALLOCATION_IN, 'amount' => $amount, 'balance_before' => $courseBefore, 'balance_after' => $courseBefore + $amount, 'related_points_transaction_id' => null, 'metadata' => ['academy_id' => $academy->id, 'purpose' => $purpose, 'source' => 'academy_allocation']]);
            $academyTx->update(['related_course_point_transaction_id' => $courseTx->id]);

            return ['academy_tx' => $academyTx->fresh(), 'course_tx' => $courseTx->fresh()];
        });
    }
}
