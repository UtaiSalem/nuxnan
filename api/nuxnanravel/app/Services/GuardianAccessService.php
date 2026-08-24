<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\ClassroomMember;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * One place that answers "may this user touch this student's guardian data?".
 *
 * The ladder is the same for every guardians.* key: the student themselves, the
 * academy owner and the homeroom staff always pass, everyone else needs the key
 * either from their academy role or from an enabled department grant.
 */
class GuardianAccessService
{
    /** Fields only the registry side may read or edit (D4/Q1). */
    public const SENSITIVE_FIELDS = ['citizen_id', 'monthly_income'];

    public function allows(?User $user, Student $student, string $permission): bool
    {
        if ($user === null) {
            return false;
        }

        // 1) the student themselves
        if ($student->user_id !== null && $user->id === $student->user_id) {
            return true;
        }

        // 2) academy owner / super admin — they normally have no academy_members row
        $academy = Academy::find($student->academy_id);
        if ($academy !== null && $academy->isAdmin($user)) {
            return true;
        }

        // 3) homeroom teacher / co-teacher of this student's own classroom
        if (ClassroomMember::isHomeroomStaffOf($user->id, $student)) {
            return true;
        }

        // 4) academy role permission, then explicit department grants
        $member = AcademyMember::where('user_id', $user->id)
            ->where('academy_id', $student->academy_id)
            ->where('status', 2)
            ->first();

        if (! $member) {
            return false;
        }

        if ($member->academyRole?->hasAnyPermission([$permission])) {
            return true;
        }

        return $academy !== null && app(AcademyGroupPermissionAccessService::class)
            ->hasAnyPermission($user, $academy, [$permission]);
    }

    public function canViewSensitive(?User $user, Student $student): bool
    {
        return $this->allows($user, $student, 'guardians.sensitive.view');
    }

    public function canManageSensitive(?User $user, Student $student): bool
    {
        return $this->allows($user, $student, 'guardians.sensitive.manage');
    }

    /**
     * Drop the sensitive fields from a model or a collection of models before it is serialized.
     * Works on both StudentGuardian (legacy) and Guardian (person) rows.
     */
    public function hideSensitive(mixed $models): mixed
    {
        if ($models === null) {
            return null;
        }

        if ($models instanceof Collection || is_array($models)) {
            foreach ($models as $model) {
                if ($model instanceof Model) {
                    $model->makeHidden(self::SENSITIVE_FIELDS);
                    // Also hide from related models if it's eagerly loaded
                    if ($model->relationLoaded('guardian') && $model->guardian) {
                        $model->guardian->makeHidden(self::SENSITIVE_FIELDS);
                    }
                }
            }
        } elseif ($models instanceof Model) {
            $models->makeHidden(self::SENSITIVE_FIELDS);
            if ($models->relationLoaded('guardian') && $models->guardian) {
                $models->guardian->makeHidden(self::SENSITIVE_FIELDS);
            }
        }

        return $models;
    }

    /**
     * Sensitive keys in $input whose value actually differs from what is stored.
     *
     * Submitting the current value is not an edit: the profile form always posts the whole
     * form, so a blanket reject would block unrelated edits.
     *
     * @return list<string>
     */
    public function changedSensitiveFields(array $input, mixed $current): array
    {
        $changed = [];

        foreach (self::SENSITIVE_FIELDS as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $inputValue = $input[$field];

            // Normalize input
            if ($inputValue === '') {
                $inputValue = null;
            }

            if ($current === null) {
                if ($inputValue !== null) {
                    $changed[] = $field;
                }

                continue;
            }

            // Extract current value based on model type (StudentGuardian vs Guardian)
            // Or if $current is an array
            $currentValue = null;
            if ($current instanceof Model) {
                if (in_array($field, ['citizen_id']) && $current->relationLoaded('guardian') && $current->guardian) {
                    $currentValue = $current->guardian->{$field} ?? $current->{$field};
                } else {
                    $currentValue = $current->{$field};
                }
            } elseif (is_array($current)) {
                $currentValue = $current[$field] ?? null;
            }

            if ($currentValue === '') {
                $currentValue = null;
            }

            if ($field === 'monthly_income') {
                if ($inputValue !== null && $currentValue !== null) {
                    if ((float) $inputValue !== (float) $currentValue) {
                        $changed[] = $field;
                    }
                } elseif ($inputValue !== $currentValue) {
                    $changed[] = $field;
                }
            } elseif ($field === 'citizen_id') {
                $inStr = $inputValue !== null ? trim((string) $inputValue) : null;
                if ($inStr === '') {
                    $inStr = null;
                }
                $curStr = $currentValue !== null ? trim((string) $currentValue) : null;
                if ($curStr === '') {
                    $curStr = null;
                }
                if ($inStr !== $curStr) {
                    $changed[] = $field;
                }
            } else {
                if ($inputValue !== $currentValue) {
                    $changed[] = $field;
                }
            }
        }

        return array_values($changed);
    }
}
