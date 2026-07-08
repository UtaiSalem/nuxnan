<?php

namespace App\Http\Requests\Academy\Enrollment;

use App\Models\Academy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UploadStudentImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $academy = $this->route('academy');

        return $academy instanceof Academy && Gate::allows('student.import', $academy);
    }

    public function rules(): array
    {
        /** @var Academy $academy */
        $academy = $this->route('academy');

        return [
            'file' => ['required', 'file', 'mimes:csv,txt,json', 'max:5120'],
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')->where('academy_id', $academy->id)],
            'import_type' => ['sometimes', 'string', Rule::in(['new_intake', 'roster_update', 'roster_reconciliation'])],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
            'defaults' => ['sometimes', 'array'],
            'defaults.grade_level' => ['nullable', 'string', 'max:10'],
            'defaults.section' => ['nullable', 'string', 'max:10'],
            'defaults.enrollment_date' => ['nullable', 'date'],
            'defaults.account_mode' => ['nullable', Rule::in(['none', 'pending_activation'])],
        ];
    }
}
