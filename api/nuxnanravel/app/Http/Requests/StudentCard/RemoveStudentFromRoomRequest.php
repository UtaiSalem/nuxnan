<?php

namespace App\Http\Requests\StudentCard;

use Illuminate\Foundation\Http\FormRequest;

class RemoveStudentFromRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('student-card.public_management');
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
