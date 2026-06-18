<?php

namespace App\Traits;

use App\Models\AcademyMember;
use App\Models\Student;
use App\Models\StudentChangeRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait HandlesStudentUpdates
{
    /**
     * Handle update of a student field with approval flow logic.
     * Staff (admin/director/teacher in the student's academy) bypass approval.
     */
    protected function applyUpdate(Student $student, string $modelType, $modelId, string $field, $newValue, $oldValue = null)
    {
        $userId = auth()->id();
        if (!$userId) {
            abort(401, 'Unauthorized to create change request');
        }

        // Staff bypass: skip approval entirely
        if ($this->isStaffOfAcademy($userId, $student->academy_id)) {
            return null;
        }

        $academy = $student->academy;
        $settings = $academy->student_editable_fields ?? [
            'mode' => 'blacklist',
            'fields' => ['citizen_id', 'student_id', 'academic', 'health']
        ];

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

            $changeRequest = $this->applyUpdate($student, $modelType, $modelId, $fullField, $value, $oldValue);

            if ($changeRequest) {
                $pending[$field] = $value;
            } else {
                $directApply[$field] = $value;
                $updated[$field] = $value;
            }
        }

        if (!empty($directApply) && $model) {
            $model->update($directApply);
        }

        return ['updated' => $updated, 'pending' => $pending];
    }

    /**
     * Determine whether the user is staff (admin/director/teacher) of the given academy.
     */
    protected function isStaffOfAcademy(int $userId, int $academyId): bool
    {
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
            if ($field === $pattern) {
                return $mode === 'blacklist';
            }
        }

        return $mode === 'whitelist';
    }
}
