<?php

namespace App\Http\Requests;

use App\Enums\StudentCardRequestReason;
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
            'request_type' => ['nullable', 'required_without:reason_code', Rule::enum(StudentCardRequestType::class)],
            'reason_code' => ['nullable', 'required_without:request_type', Rule::enum(StudentCardRequestReason::class)],
            'priority' => ['nullable', Rule::in(['normal', 'urgent'])],
            'reason' => ['nullable', 'string', 'max:2000', 'required_if:reason_code,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required_if' => 'กรุณาระบุรายละเอียดเมื่อเลือกเหตุผล "อื่นๆ"',
            'request_type.required_without' => 'กรุณาระบุประเภทคำร้องหรือเหตุผลการขอทำบัตร',
            'reason_code.required_without' => 'กรุณาระบุประเภทคำร้องหรือเหตุผลการขอทำบัตร',
        ];
    }
}
