<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateCourseRequest
 *
 * ใช้แทนการรับ $request->all() ใน admin course update route
 * เพื่อปิดช่อง mass assignment และ validate ข้อมูลทุกฟิลด์.
 */
class UpdateCourseRequest extends FormRequest
{
    /**
     * Authorization ตรวจใน middleware `permission:course-edit` อยู่แล้ว
     * ที่ route-level จึงคืน true ที่นี่ (หรือจะย้ายมาอยู่ที่นี่ก็ได้).
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:course_categories,id'],
            'level' => ['sometimes', 'nullable', 'string', 'max:50'],
            'education_level' => ['sometimes', 'nullable', Rule::in(['ประถมศึกษา', 'มัธยมศึกษา', 'ปวช.', 'ปวส.', 'อุดมศึกษา', 'อื่นๆ'])],
            'education_year' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:6'],
            'language' => ['sometimes', 'nullable', 'string', 'max:50'],

            'duration' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'class_schedule' => ['sometimes', 'nullable', 'string'],
            'semester' => ['sometimes', 'nullable', Rule::in(['1', '2', '3', 'summer', 'weekend'])],
            'academic_year' => ['sometimes', 'nullable', 'integer', 'min:2560', 'max:2580'],
            'hours_per_week' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'tuition_fees' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'discount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'discount_type' => ['sometimes', Rule::in(['fixed', 'percent'])],
            'points' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'saleable' => ['sometimes', 'boolean'],

            'credit_units' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'enrolled_students' => ['sometimes', 'integer', 'min:0'],

            'prerequisites' => ['sometimes', 'nullable', 'string'],
            'course_materials' => ['sometimes', 'nullable', 'string'],
            'syllabus' => ['sometimes', 'nullable', 'string'],

            'cover' => ['sometimes', 'nullable', 'string', 'max:255'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cover_header' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cover_subheader' => ['sometimes', 'nullable', 'string'],

            'accreditation' => ['sometimes', 'nullable', 'string', 'max:100'],
            'accreditation_body' => ['sometimes', 'nullable', 'string', 'max:100'],
            'certificate' => ['sometimes', 'boolean'],
            'certificate_download_cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'certificate_free_download' => ['sometimes', 'boolean'],

            'status' => ['sometimes', Rule::in(['draft', 'pending', 'published', 'archived', 1, 2, 3, 4])],
            'rating' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:5'],

            // Grading / remediation — admin-managed
            'max_absence_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'allow_unlock_by_points' => ['sometimes', 'boolean'],
            'unlock_points_cost' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'allow_unlock_by_reading' => ['sometimes', 'boolean'],
            'unlock_reading_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'allow_remediation' => ['sometimes', 'boolean'],
            'max_remediation_attempts' => ['sometimes', 'integer', 'min:0'],
            'remediation_max_grade' => ['sometimes', 'string', 'max:2'],
            'allow_grade_appeal' => ['sometimes', 'boolean'],
            'appeal_deadline_days' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
