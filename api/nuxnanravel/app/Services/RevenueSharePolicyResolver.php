<?php

namespace App\Services;

use App\Models\RevenueSharePolicy;
use Carbon\Carbon;

class RevenueSharePolicyResolver
{
    public function resolve(?int $campaignId, ?int $courseId, ?int $academyId, ?Carbon $at = null): RevenueSharePolicy
    {
        $at ??= now();
        foreach ([[RevenueSharePolicy::SCOPE_CAMPAIGN, $campaignId], [RevenueSharePolicy::SCOPE_COURSE, $courseId], [RevenueSharePolicy::SCOPE_ACADEMY, $academyId], [RevenueSharePolicy::SCOPE_PLATFORM, null]] as [$scope, $id]) {
            $policy = RevenueSharePolicy::query()->where('scope_type', $scope)->when($id === null, fn ($q) => $q->whereNull('scope_id'), fn ($q) => $q->where('scope_id', $id))->active($at)->orderByDesc('version')->first();
            if ($policy) {
                if (! $policy->sumsTo100()) {
                    throw new \DomainException('Revenue share policy must sum to 100.00.');
                }

                return $policy;
            }
        }
        throw new \DomainException('No active revenue share policy found.');
    }

    public function split(int|float $grossAmount, RevenueSharePolicy $policy): array
    {
        $gross = max(0, (int) floor($grossAmount));
        $student = min($gross, (int) floor($gross * (float) $policy->student_pct / 100));
        $course = min($gross - $student, (int) floor($gross * (float) $policy->course_pct / 100));
        $platform = max(0, $gross - $student - $course);

        return compact('student', 'course', 'platform') + ['policy_version' => (int) $policy->version, 'policy_id' => (int) $policy->id];
    }
}
