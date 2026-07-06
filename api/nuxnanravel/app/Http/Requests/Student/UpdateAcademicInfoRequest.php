<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_grade' => 'nullable|string|max:10',
            'education_level' => 'nullable|integer|in:0,1,2',
            'current_class' => 'nullable|string|max:10',
            'classroom_full' => 'nullable|string|max:20',
            'school_name' => 'nullable|string|max:200',
            'school_address' => 'nullable|string',
            'school_province' => 'nullable|string|max:100',
            'previous_school_name' => 'nullable|string|max:200',
            'previous_school_province' => 'nullable|string|max:100',
            'previous_grade_level' => 'nullable|string|max:20',
            'disability_type' => 'nullable|string|max:100',
            'special_needs' => 'nullable|string',
            'academic_year' => 'nullable|string|max:10',
            'semester' => 'nullable|integer|in:1,2',
            'enrollment_date' => 'nullable|date',
            'graduation_date' => 'nullable|date|after_or_equal:enrollment_date',
            'study_status' => 'nullable|in:studying,graduated,transferred,dropped,suspended',
            'is_current' => 'nullable|boolean',
            'transfer_reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ];
    }
}
