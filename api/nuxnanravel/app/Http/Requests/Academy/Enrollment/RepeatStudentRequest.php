<?php

namespace App\Http\Requests\Academy\Enrollment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RepeatStudentRequest extends FormRequest
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
            'new_classroom_id' => [
                'required',
                'integer',
                Rule::exists('classrooms', 'id')->where(fn ($query) => $query->where('academy_id', $this->academyId())),
            ],
            'student_number' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function academyId(): int
    {
        return (int) ($this->route('academy')?->id ?? 0);
    }
}
