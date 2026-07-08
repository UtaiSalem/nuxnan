<?php

namespace App\Http\Requests;

use App\Enums\StudentCardRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStoreStudentCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requests' => ['required', 'array', 'min:1', 'max:100'],
            'requests.*.student_id' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'requests.*.classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'requests.*.request_type' => ['required', Rule::enum(StudentCardRequestType::class)],
            'requests.*.priority' => ['nullable', Rule::in(['normal', 'urgent'])],
            'requests.*.reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
