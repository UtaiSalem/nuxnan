<?php

namespace App\Services\Campaign;

use App\Models\Academy;
use App\Models\Advert;
use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class CampaignAuthorizationService
{
    public function canCreate(User $user, string $scope, ?Academy $academy = null, ?Course $course = null): bool
    {
        if ($user->isSuperAdmin() || $scope === 'public') {
            return true;
        }

        if ($scope === 'academy') {
            return $academy !== null && $academy->isAdmin($user);
        }

        return $scope === 'course'
            && $course !== null
            && $course->isAdmin($user)
            && ($course->academy_id === null || $academy === null || (int) $course->academy_id === (int) $academy->id);
    }

    public function assertCanCreate(User $user, string $scope, ?Academy $academy = null, ?Course $course = null): void
    {
        if (! $this->canCreate($user, $scope, $academy, $course)) {
            throw new AuthorizationException('คุณไม่มีสิทธิ์สร้างแคมเปญในพื้นที่นี้');
        }
    }

    public function canReview(User $user, Advert $campaign): bool
    {
        if ($user->isSuperAdmin() || $user->hasAnyRole(['ADMIN', 'PLEARND_ADMIN'])) {
            return true;
        }

        if ($campaign->scope_type?->value === 'academy') {
            return $campaign->academy?->isAdmin($user) ?? false;
        }

        if ($campaign->scope_type?->value === 'course') {
            return $campaign->course?->isAdmin($user) ?? false;
        }

        return false;
    }
}
