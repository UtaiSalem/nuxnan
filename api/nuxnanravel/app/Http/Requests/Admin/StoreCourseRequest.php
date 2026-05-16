<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreCourseRequest
 *
 * ใช้ตอนสร้าง course ใหม่ใน admin route (แทน inline validate()).
 */
class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            'price'       => ['nullable', 'numeric', 'min:0'],
            'status'           => ['nullable', 'in:draft,pending,published,archived'],
            'education_level'  => ['nullable', 'string', 'max:30'],
            'education_year'   => ['nullable', 'integer', 'min:1', 'max:6'],
        ];
    }
}
