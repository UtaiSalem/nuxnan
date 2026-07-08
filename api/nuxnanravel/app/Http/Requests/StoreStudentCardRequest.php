<?php

namespace App\Http\Requests;

use App\Enums\StudentCardRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'request_type' => ['required', Rule::enum(StudentCardRequestType::class)],
            'priority' => ['nullable', Rule::in(['normal', 'urgent'])],
            'reason' => ['nullable', 'string', 'max:2000', Rule::requiredIf(fn () => in_array($this->input('request_type'), ['replacement', 'renewal'], true))],
        ];
    }
}
