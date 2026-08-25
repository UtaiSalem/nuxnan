<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\ClassroomMember;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentGuardianLink;
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

    /**
     * Which of the three appointment routes this user is taking for this student.
     *
     * The ladder is the same order as allows(), so the label always matches the reason
     * the user was let through. Stored on student_guardian_links.appointed_by_role.
     */
    public function actorRole(?User $user, Student $student): string
    {
        if ($user === null) {
            return 'system';
        }

        if ($student->user_id !== null && $user->id === $student->user_id) {
            return 'student';
        }

        if (Academy::find($student->academy_id)?->isAdmin($user)) {
            return 'owner';
        }

        if (ClassroomMember::isHomeroomStaffOf($user->id, $student)) {
            return 'homeroom';
        }

        return 'staff';
    }

    /**
     * Is this user a guardian of this student?
     *
     * Answered by the account link on the guardian person. The four call sites this replaced each
     * matched on $user->citizen_id or $user->phone, and neither is a column on users — the phone
     * column is phone_number and there is no citizen id at all — so all four evaluated to false
     * for every user who ever called them. Nothing is lost by dropping that, and nothing is
     * widened: guardians.user_id is what the parent-account flow (G-S12) will populate, and
     * until it ships there are no parent accounts to let in.
     */
    public function isGuardianOf(?User $user, Student $student): bool
    {
        if ($user === null) {
            return false;
        }

        return StudentGuardianLink::query()
            ->where('student_id', $student->id)
            ->whereIn('guardian_id', Guardian::query()->select('id')->where('user_id', $user->id))
            ->exists();
    }

    /**
     * The students in one academy this user is a guardian of.
     *
     * @return list<int>
     */
    public function guardianStudentIds(?User $user, Academy $academy): array
    {
        if ($user === null) {
            return [];
        }

        return StudentGuardianLink::query()
            ->whereIn('guardian_id', Guardian::query()->select('id')->where('user_id', $user->id))
            ->whereIn('student_id', Student::query()->select('id')->where('academy_id', $academy->id))
            ->pluck('student_id')
            ->unique()
            ->values()
            ->all();
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
     * Works on both StudentGuardianLink and Guardian (person) rows.
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
     * Guardian rows this student attached to themselves that staff has not confirmed yet.
     *
     * Sharing one guardian record between siblings is the point of the person-level model, but it also
     * means a student who knows someone's citizen id could attach that person and read their income.
     * So the gate is narrow on purpose: it closes only when the student reached for a person who
     * already belongs to somebody else, and it opens again the moment a teacher or the registrar
     * verifies the link. A guardian the student typed in themselves has exactly one link and is
     * never blocked — hiding the citizen id they just entered would be nonsense.
     *
     * @return array{link: list<int>, person: list<int>}
     */
    public function unverifiedSelfAppointedIds(Student $student): array
    {
        $empty = ['link' => [], 'person' => []];

        $links = StudentGuardianLink::query()
            ->where('student_id', $student->id)
            ->where('appointed_by_role', 'student')
            ->whereNull('verified_at')
            ->get();

        if ($links->isEmpty()) {
            return $empty;
        }

        // Only a person shared with another student is someone else's; a freshly typed one is not.
        $sharedPersonIds = StudentGuardianLink::query()
            ->whereIn('guardian_id', $links->pluck('guardian_id')->all())
            ->select('guardian_id')
            ->groupBy('guardian_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('guardian_id')
            ->all();

        if ($sharedPersonIds === []) {
            return $empty;
        }

        $blocked = $links->whereIn('guardian_id', $sharedPersonIds);

        return [
            'link' => $blocked->pluck('id')->values()->all(),
            'person' => $blocked->pluck('guardian_id')->unique()->values()->all(),
        ];
    }

    /**
     * The same gate as a yes/no question, for the callers that build plain arrays instead of
     * serializing models. Pass the ids from unverifiedSelfAppointedIds() so the query runs once.
     *
     * @param  array{link: list<int>, person: list<int>}  $blockedIds
     */
    public function isBlockedGuardianRow(array $blockedIds, mixed $row): bool
    {
        if (! $row instanceof Model) {
            return false;
        }

        return match (true) {
            $row instanceof StudentGuardianLink => in_array($row->id, $blockedIds['link'], true),
            $row instanceof Guardian => in_array($row->id, $blockedIds['person'], true),
            default => false,
        };
    }

    /** True when this viewer must not see the sensitive fields of this one guardian row. */
    public function blocksSensitiveRow(?User $user, Student $student, mixed $row): bool
    {
        if ($user === null || $student->user_id === null || $student->user_id !== $user->id) {
            return false;
        }

        return $this->isBlockedGuardianRow($this->unverifiedSelfAppointedIds($student), $row);
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

            // Extract current value based on model type (StudentGuardianLink vs Guardian)
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
