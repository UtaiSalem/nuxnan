<?php

namespace App\Traits;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Student;
use App\Models\StudentChangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait HandlesStudentUpdates
{
    /**
     * Fields a student may NOT change on their own without staff approval.
     * Identity fields (name, prefix, gender, date of birth) are included because
     * they feed student cards, transcripts and the sports-day gender balance.
     */
    public static function defaultEditableFieldSettings(): array
    {
        return [
            'mode' => 'blacklist',
            'fields' => [
                'citizen_id',
                'student_id',
                'academic',
                'health',
                'gender',
                'date_of_birth',
                'title_prefix_th',
                'title_prefix_en',
                'first_name_th',
                'first_name_en',
                'last_name_th',
                'last_name_en',
            ],
        ];
    }

    /**
     * Handle update of a student field with approval flow logic.
     * Staff (admin/director/teacher in the student's academy) bypass approval.
     */
    protected function applyUpdate(Student $student, string $modelType, $modelId, string $field, $newValue, $oldValue = null)
    {
        $userId = auth()->id();
        if (! $userId) {
            abort(401, 'Unauthorized to create change request');
        }

        // Staff bypass: skip approval entirely
        if ($this->isStaffOfAcademy($userId, $student->academy_id)) {
            return null;
        }

        $academy = $student->academy;
        $settings = $academy->student_editable_fields ?? self::defaultEditableFieldSettings();

        if ($this->needsApproval($field, $settings)) {
            return StudentChangeRequest::create([
                'academy_id' => $student->academy_id,
                'student_id' => $student->id,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'status' => 'pending',
                'requested_by' => $userId,
            ]);
        }

        // Direct update logic should be handled by the caller or specialized method
        return null;
    }

    /**
     * Process an array of field updates against a model.
     * For each field: route to change request (if owner + needs approval) or apply directly.
     * Returns ['updated' => [...], 'pending' => [...]] summary.
     */
    protected function processFieldUpdates(Student $student, ?Model $model, string $modelType, string $fieldPrefix, array $newValues): array
    {
        $modelId = $model?->id;
        $updated = [];
        $pending = [];
        $directApply = [];

        foreach ($newValues as $field => $value) {
            $fullField = $fieldPrefix ? "{$fieldPrefix}.{$field}" : $field;
            $oldValue = $model?->{$field};

            // Sectional forms post every field, changed or not. Without this the
            // owner would raise a change request per untouched blacklisted field.
            if ($model && $this->valuesMatch($oldValue, $value)) {
                continue;
            }

            $changeRequest = $this->applyUpdate($student, $modelType, $modelId, $fullField, $value, $oldValue);

            if ($changeRequest) {
                $pending[$field] = $value;
            } else {
                $directApply[$field] = $value;
                $updated[$field] = $value;
            }
        }

        if (! empty($directApply) && $model) {
            $model->update($directApply);
        }

        return ['updated' => $updated, 'pending' => $pending];
    }

    /**
     * Compare a stored attribute against an incoming value, tolerating the
     * casts Eloquent applies (Carbon dates, integer gender) versus the plain
     * strings that arrive from a form.
     */
    protected function valuesMatch($oldValue, $newValue): bool
    {
        if ($oldValue instanceof \DateTimeInterface) {
            $oldValue = $oldValue->format('Y-m-d');
        }

        if ($newValue instanceof \DateTimeInterface) {
            $newValue = $newValue->format('Y-m-d');
        }

        if ($oldValue === null || $newValue === null) {
            return $oldValue === $newValue;
        }

        if (is_array($oldValue) || is_array($newValue)) {
            return $oldValue === $newValue;
        }

        return (string) $oldValue === (string) $newValue;
    }

    /**
     * Determine whether the user is staff (admin/director/teacher) of the given academy.
     */
    protected function isStaffOfAcademy(int $userId, int $academyId): bool
    {
        // The academy owner normally has no academy_members row.
        $academy = Academy::find($academyId);
        if ($academy && $academy->isAdmin(User::find($userId))) {
            return true;
        }

        return AcademyMember::where('user_id', $userId)
            ->where('academy_id', $academyId)
            ->whereIn('role', ['admin', 'director', 'teacher'])
            ->exists();
    }

    /**
     * Check if a field requires administrative approval.
     */
    protected function needsApproval(string $field, array $settings): bool
    {
        $mode = $settings['mode'] ?? 'blacklist';
        $fields = $settings['fields'] ?? [];

        // Check for wildcard patterns like "academic.*"
        foreach ($fields as $pattern) {
            if (str_contains($pattern, '*')) {
                $base = str_replace('.*', '', $pattern);
                if (str_starts_with($field, $base)) {
                    return $mode === 'blacklist';
                }
            }
            if ($field === $pattern || str_starts_with($field, $pattern.'.')) {
                return $mode === 'blacklist';
            }
        }

        return $mode === 'whitelist';
    }
}
