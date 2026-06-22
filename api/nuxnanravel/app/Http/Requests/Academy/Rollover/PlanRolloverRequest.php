<?php

namespace App\Http\Requests\Academy\Rollover;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PlanRolloverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('enrollment.plan', $this->route('academy'));
    }

    public function rules(): array
    {
        return [
            'from_year_id' => [
                'required',
                'integer',
                Rule::exists('academic_years', 'id')->where(fn ($query) => $query->where('academy_id', $this->academyId())),
            ],
            'to_year_id' => [
                'required',
                'integer',
                'different:from_year_id',
                Rule::exists('academic_years', 'id')->where(fn ($query) => $query->where('academy_id', $this->academyId())),
            ],
            'mapping' => ['required', 'array', 'min:1'],
            'mapping.*.student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where(fn ($query) => $query->where('academy_id', $this->academyId())),
            ],
            'mapping.*.action' => ['required', Rule::in(['promote', 'graduate', 'drop', 'repeat', 'new_intake', 'skip'])],
            'mapping.*.from_classroom_id' => [
                'nullable',
                'integer',
                Rule::exists('classrooms', 'id')->where(fn ($query) => $query->where('academy_id', $this->academyId())),
            ],
            'mapping.*.to_classroom_id' => [
                'nullable',
                'integer',
                Rule::exists('classrooms', 'id')->where(fn ($query) => $query->where('academy_id', $this->academyId())),
            ],
            'mapping.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach ($this->input('mapping', []) as $index => $entry) {
                $action = $entry['action'] ?? null;
                $hasTo = filled($entry['to_classroom_id'] ?? null);

                if (in_array($action, ['promote', 'repeat', 'new_intake'], true) && ! $hasTo) {
                    $validator->errors()->add("mapping.$index.to_classroom_id", 'กรุณาระบุห้องปลายทางสำหรับ action นี้');
                }

                if (in_array($action, ['graduate', 'drop', 'skip'], true) && $hasTo) {
                    $validator->errors()->add("mapping.$index.to_classroom_id", 'action นี้ต้องไม่ระบุห้องปลายทาง');
                }
            }
        });
    }

    private function academyId(): int
    {
        return (int) ($this->route('academy')?->id ?? 0);
    }
}
