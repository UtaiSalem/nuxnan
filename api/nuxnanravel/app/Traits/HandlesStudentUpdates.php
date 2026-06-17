<?php

namespace App\Traits;

use App\Models\Student;
use App\Models\StudentChangeRequest;
use Illuminate\Http\Request;

trait HandlesStudentUpdates
{
    /**
     * Handle update of a student field with approval flow logic.
     */
    protected function applyUpdate(Student $student, string $modelType, $modelId, string $field, $newValue, $oldValue = null)
    {
        $academy = $student->academy;
        $settings = $academy->student_editable_fields ?? [
            'mode' => 'blacklist',
            'fields' => ['citizen_id', 'student_id', 'academic', 'health']
        ];

        if ($this->needsApproval($field, $settings)) {
            $userId = auth()->id();
            if (!$userId) {
                abort(401, 'Unauthorized to create change request');
            }

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
