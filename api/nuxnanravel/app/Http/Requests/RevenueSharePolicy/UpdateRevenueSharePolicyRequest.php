<?php

namespace App\Http\Requests\RevenueSharePolicy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateRevenueSharePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlearndAdmin() === true || $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return ['scope_type' => 'sometimes|in:platform,academy,course,campaign', 'scope_id' => 'sometimes|nullable|integer', 'student_pct' => 'sometimes|numeric|between:0,100', 'course_pct' => 'sometimes|numeric|between:0,100', 'academy_pct' => 'sometimes|numeric|between:0,100', 'platform_pct' => 'sometimes|numeric|between:0,100', 'effective_from' => 'sometimes|date', 'effective_to' => 'sometimes|nullable|date', 'notes' => 'sometimes|nullable|string|max:500'];
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('policy')) {
            $p = $this->route('policy');
            $this->merge(array_merge(['scope_type' => $p->scope_type, 'scope_id' => $p->scope_id, 'student_pct' => $p->student_pct, 'course_pct' => $p->course_pct, 'academy_pct' => $p->academy_pct, 'platform_pct' => $p->platform_pct, 'effective_from' => $p->effective_from, 'effective_to' => $p->effective_to, 'notes' => $p->notes], $this->all()));
        }
    }

    public function withValidator(Validator $v): void
    {
        $v->after(function (Validator $v) {
            if ($this->all() === []) {
                $v->errors()->add('policy', 'At least one field must be changed.');
            } if ($this->scope_type !== 'platform' && blank($this->scope_id)) {
                $v->errors()->add('scope_id', 'Scope ID is required.');
            } if ($v->errors()->isEmpty() && round((float) $this->student_pct + (float) $this->course_pct + (float) $this->academy_pct + (float) $this->platform_pct, 2) !== 100.0) {
                $v->errors()->add('percentages', 'Percentages must sum to 100.00.');
            }
        });
    }
}
