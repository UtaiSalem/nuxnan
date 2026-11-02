<?php

namespace App\Http\Requests\Learn\Course;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseAffiliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ตรวจสิทธิ์ใน controller (ต้องใช้ทั้ง course และ academy)
    }

    public function rules(): array
    {
        return [
            'academy_id' => ['required', 'integer', 'exists:academies,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'academy_id.required' => 'กรุณาเลือกโรงเรียน',
            'academy_id.exists' => 'ไม่พบโรงเรียนที่เลือก',
        ];
    }
}
