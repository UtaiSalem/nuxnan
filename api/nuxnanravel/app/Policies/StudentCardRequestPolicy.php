<?php

namespace App\Policies;

use App\Models\AcademyMember;
use App\Models\StudentCardRequest;
use App\Models\User;

class StudentCardRequestPolicy
{
    public function view(User $user, StudentCardRequest $request): bool
    {
        return $this->hasPermission($user, $request, 'students.cards.produce')
            || (int) $request->classroom?->homeroom_teacher_id === (int) $user->id;
    }

    public function manage(User $user, StudentCardRequest $request): bool
    {
        return $this->hasPermission($user, $request, 'students.cards.produce');
    }

    public function cancel(User $user, StudentCardRequest $request): bool
    {
        return (int) $request->requested_by === (int) $user->id;
    }

    private function hasPermission(User $user, StudentCardRequest $request, string $permission): bool
    {
        if ($user->isSuperAdmin() || $request->academy?->isAdmin($user)) {
            return true;
        }

        return AcademyMember::query()
            ->where('academy_id', $request->academy_id)
            ->where('user_id', $user->id)
            ->where('status', 2)
            ->with('academyRole')
            ->first()?->hasPermission($permission) ?? false;
    }
}
