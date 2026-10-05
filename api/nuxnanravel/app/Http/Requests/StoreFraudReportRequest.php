<?php

namespace App\Http\Requests;

use App\Models\AccountFraudReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFraudReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reported_user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::notIn([$this->user()?->id]),
            ],
            'category' => ['required', 'string', Rule::in(AccountFraudReport::CATEGORIES)],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'related_transaction_type' => ['nullable', 'string', Rule::in(['points', 'wallet'])],
            'related_transaction_id' => ['nullable', 'integer', 'required_with:related_transaction_type'],
            'evidence_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reported_user_id.not_in' => 'ไม่สามารถร้องเรียนบัญชีของตนเองได้',
            'reported_user_id.exists' => 'ไม่พบบัญชีที่ต้องการร้องเรียน',
            'category.in' => 'ประเภทการร้องเรียนไม่ถูกต้อง',
            'description.min' => 'กรุณาอธิบายรายละเอียดอย่างน้อย 10 ตัวอักษร',
            'description.required' => 'กรุณาอธิบายรายละเอียดการร้องเรียน',
            'related_transaction_id.required_with' => 'กรุณาระบุรายการธุรกรรมที่เกี่ยวข้อง',
        ];
    }
}
