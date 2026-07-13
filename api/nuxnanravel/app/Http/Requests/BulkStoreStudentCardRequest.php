<?php

namespace App\Http\Requests;

use App\Enums\StudentCardRequestReason;
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
            'requests.*.request_type' => ['nullable', 'required_without:requests.*.reason_code', Rule::enum(StudentCardRequestType::class)],
            'requests.*.reason_code' => ['nullable', 'required_without:requests.*.request_type', Rule::enum(StudentCardRequestReason::class)],
            'requests.*.priority' => ['nullable', Rule::in(['normal', 'urgent'])],
            'requests.*.reason' => ['nullable', 'string', 'max:2000', 'required_if:requests.*.reason_code,other'],
        ];
    }
}
