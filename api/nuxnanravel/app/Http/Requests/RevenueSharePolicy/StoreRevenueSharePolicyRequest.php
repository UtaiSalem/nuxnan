<?php

namespace App\Http\Requests\RevenueSharePolicy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRevenueSharePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlearndAdmin() === true || $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return ['scope_type' => 'required|in:platform,academy,course,campaign', 'scope_id' => 'required_unless:scope_type,platform|nullable|integer', 'student_pct' => 'required|numeric|between:0,100', 'course_pct' => 'required|numeric|between:0,100', 'academy_pct' => 'required|numeric|between:0,100', 'platform_pct' => 'required|numeric|between:0,100', 'effective_from' => 'required|date', 'effective_to' => 'nullable|date|after:effective_from', 'notes' => 'nullable|string|max:500'];
    }

    public function withValidator(Validator $v): void
    {
        $v->after(function (Validator $v) {
            if ($v->errors()->isEmpty() && round((float) $this->student_pct + (float) $this->course_pct + (float) $this->academy_pct + (float) $this->platform_pct, 2) !== 100.0) {
                $v->errors()->add('percentages', 'Percentages must sum to 100.00.');
            }
        });
    }
}
