<?php

namespace App\Http\Requests\StudentCard;

use Illuminate\Foundation\Http\FormRequest;

class TransferStudentFromRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('student-card.public_management');
    }

    public function rules(): array
    {
        return [
            'to_classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'to_classroom_id.required' => 'กรุณาเลือกห้องปลายทาง',
            'to_classroom_id.exists' => 'ไม่พบห้องปลายทาง',
        ];
    }
}
