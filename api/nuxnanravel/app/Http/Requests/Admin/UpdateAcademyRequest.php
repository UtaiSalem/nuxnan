<?php

namespace App\Http\Requests\Admin;

use App\Models\Academy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateAcademyRequest
 *
 * ใช้แทน $request->all() ใน admin academy update route
 * ป้องกัน mass assignment และ validate ฟิลด์.
 */
class UpdateAcademyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'slogan' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            // SET-S7 / G22 — เดิมเป็น string ทำให้เขียนชื่อคนลงไปแล้วหน้าโรงเรียน 500
            'director' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            // D16 — พ.ศ. ไม่ใช่ ค.ศ.
            'established_year' => ['sometimes', 'nullable', 'integer', 'min:2400', 'max:'.(now()->year + 543)],
            'type' => ['sometimes', 'nullable', 'string', Rule::in(Academy::ACADEMY_TYPE_CATALOG)],
            'accreditation' => ['sometimes', 'nullable', 'string', 'max:100'],
            'accreditation_body' => ['sometimes', 'nullable', 'string', 'max:100'],
            'total_students' => ['sometimes', 'integer', 'min:0'],
            'total_teachers' => ['sometimes', 'integer', 'min:0'],
            'membership_fees_points' => ['sometimes', 'integer', 'min:0'],
            'courses_offered' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'facilities' => ['sometimes', 'nullable', 'string'],
            'academy_timings' => ['sometimes', 'nullable', 'string'],
            'holidays' => ['sometimes', 'nullable', 'string'],
            'social_media_links' => ['sometimes', 'nullable', 'array'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cover' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];

        foreach (Academy::SOCIAL_LINK_CATALOG as $socialKey) {
            $rules["social_media_links.{$socialKey}"] = ['nullable', 'url', 'max:255'];
        }

        return $rules;
    }
}
