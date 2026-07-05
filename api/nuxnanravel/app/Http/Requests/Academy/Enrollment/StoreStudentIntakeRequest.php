<?php

namespace App\Http\Requests\Academy\Enrollment;

use App\Models\Academy;
use App\Models\Classroom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStudentIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $academy = $this->route('academy');

        return $academy instanceof Academy && Gate::allows('student.intake', $academy);
    }

    protected function prepareForValidation(): void
    {
        $identity = $this->input('identity', []);
        if (($identity['citizen_id'] ?? null) === '') {
            $identity['citizen_id'] = null;
        }

        $account = $this->input('account', []);
        $account['mode'] = $account['mode'] ?? 'pending_activation';

        $this->merge(['identity' => $identity, 'account' => $account]);
    }

    public function rules(): array
    {
        /** @var Academy $academy */
        $academy = $this->route('academy');

        return [
            'identity' => ['required', 'array'],
            'identity.student_id' => ['required', 'string', 'max:20', Rule::unique('students', 'student_id')->where('academy_id', $academy->id)],
            'identity.citizen_id' => ['nullable', 'digits:13', Rule::unique('students', 'citizen_id')->where('academy_id', $academy->id)],
            'personal' => ['required', 'array'],
            'personal.title_prefix_th' => ['nullable', 'string', 'max:20'],
            'personal.first_name_th' => ['required', 'string', 'max:100'],
            'personal.last_name_th' => ['required', 'string', 'max:100'],
            'personal.middle_name_th' => ['nullable', 'string', 'max:100'],
            'personal.title_prefix_en' => ['nullable', 'string', 'max:20'],
            'personal.first_name_en' => ['nullable', 'string', 'max:100'],
            'personal.last_name_en' => ['nullable', 'string', 'max:100'],
            'personal.middle_name_en' => ['nullable', 'string', 'max:100'],
            'personal.nickname' => ['nullable', 'string', 'max:50'],
            'personal.date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'personal.gender' => ['nullable', 'integer', Rule::in([0, 1])],
            'personal.nationality' => ['nullable', 'string', 'max:50'],
            'personal.religion' => ['nullable', 'string', 'max:50'],
            'admission' => ['required', 'array'],
            'admission.academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')->where('academy_id', $academy->id)],
            'admission.classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')->where('academy_id', $academy->id)],
            'admission.student_number' => ['nullable', 'integer', 'min:1'],
            'admission.enrollment_date' => ['required', 'date'],
            'previous_school' => ['sometimes', 'array'],
            'previous_school.name' => ['nullable', 'string', 'max:200'],
            'previous_school.province' => ['nullable', 'string', 'max:100'],
            'previous_school.grade_level' => ['nullable', 'string', 'max:20'],
            'guardians' => ['sometimes', 'array', 'max:4'],
            'guardians.*.guardian_type' => ['required', Rule::in(['father', 'mother', 'guardian', 'relative', 'other'])],
            'guardians.*.citizen_id' => ['nullable', 'digits:13'],
            'guardians.*.title_prefix' => ['nullable', 'string', 'max:20'],
            'guardians.*.first_name' => ['required', 'string', 'max:100'],
            'guardians.*.last_name' => ['required', 'string', 'max:100'],
            'guardians.*.occupation' => ['nullable', 'string', 'max:100'],
            'guardians.*.workplace' => ['nullable', 'string', 'max:200'],
            'guardians.*.monthly_income' => ['nullable', 'numeric', 'min:0'],
            'guardians.*.relationship' => ['nullable', 'string', 'max:50'],
            'guardians.*.status' => ['nullable', Rule::in(['alive', 'deceased', 'unknown'])],
            'guardians.*.nationality' => ['nullable', 'string', 'max:50'],
            'guardians.*.is_primary_contact' => ['sometimes', 'boolean'],
            'guardians.*.is_emergency_contact' => ['sometimes', 'boolean'],
            'guardians.*.contacts' => ['sometimes', 'array', 'max:5'],
            'guardians.*.contacts.*.contact_type' => ['required', Rule::in(['mobile', 'phone', 'email', 'line', 'other'])],
            'guardians.*.contacts.*.contact_value' => ['required', 'string', 'max:255'],
            'guardians.*.contacts.*.is_primary' => ['sometimes', 'boolean'],
            'account' => ['required', 'array'],
            'account.mode' => ['required', Rule::in(['none', 'pending_activation'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $classroom = Classroom::find($this->integer('admission.classroom_id'));
            if ($classroom && $classroom->academic_year_id !== $this->integer('admission.academic_year_id')) {
                $validator->errors()->add('admission.classroom_id', 'The classroom must belong to the selected academic year.');
            }
        }];
    }
}
