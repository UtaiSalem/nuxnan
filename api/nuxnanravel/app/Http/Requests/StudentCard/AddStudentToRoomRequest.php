<?php

namespace App\Http\Requests\StudentCard;

use Illuminate\Foundation\Http\FormRequest;

class AddStudentToRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('student-card.public_management');
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'student_number' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'กรุณาระบุนักเรียน',
            'student_id.exists' => 'ไม่พบนักเรียนในระบบ',
            'student_number.min' => 'เลขที่ต้องเป็นตัวเลขมากกว่า 0',
        ];
    }
}
