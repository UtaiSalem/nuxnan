<?php

namespace App\Http\Requests\Academy\Enrollment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class GraduateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('enrollment.lifecycle', [
            $this->route('academy'),
            $this->route('student'),
        ]);
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
            'effective_at' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'effective_at.before_or_equal' => 'วันที่จบการศึกษาต้องไม่เป็นอนาคต',
        ];
    }
}
