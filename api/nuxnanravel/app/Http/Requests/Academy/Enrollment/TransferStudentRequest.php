<?php

namespace App\Http\Requests\Academy\Enrollment;

use App\Models\Classroom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TransferStudentRequest extends FormRequest
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
            'from_classroom_id' => [
                'required',
                'integer',
                Rule::exists('classrooms', 'id')->where(fn ($query) => $query->where('academy_id', $this->academyId())),
            ],
            'to_classroom_id' => [
                'required',
                'integer',
                'different:from_classroom_id',
                Rule::exists('classrooms', 'id')->where(fn ($query) => $query->where('academy_id', $this->academyId())),
            ],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->hasAny(['from_classroom_id', 'to_classroom_id'])) {
                return;
            }

            $fromClassroom = $this->resolveClassroom($this->input('from_classroom_id'));
            $toClassroom = $this->resolveClassroom($this->input('to_classroom_id'));

            if (! $fromClassroom || ! $toClassroom) {
                return;
            }

            if ($fromClassroom->academic_year_id !== $toClassroom->academic_year_id) {
                $validator->errors()->add(
                    'to_classroom_id',
                    'ห้องปลายทางต้องอยู่ในปีการศึกษาเดียวกัน (ใช้ promote สำหรับข้ามปี)'
                );
            }
        });
    }

    private function academyId(): int
    {
        return (int) ($this->route('academy')?->id ?? 0);
    }

    private function resolveClassroom(mixed $classroomId): ?Classroom
    {
        if (! is_numeric($classroomId)) {
            return null;
        }

        return Classroom::query()
            ->where('academy_id', $this->academyId())
            ->whereKey((int) $classroomId)
            ->first();
    }
}
