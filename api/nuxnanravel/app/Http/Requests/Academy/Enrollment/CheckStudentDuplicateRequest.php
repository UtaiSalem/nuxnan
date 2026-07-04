<?php

namespace App\Http\Requests\Academy\Enrollment;

use App\Models\Academy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CheckStudentDuplicateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $academy = $this->route('academy');

        return $academy instanceof Academy && Gate::allows('student.intake', $academy);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['nullable', 'string', 'max:20', 'required_without:citizen_id'],
            'citizen_id' => ['nullable', 'digits:13', 'required_without:student_id'],
        ];
    }
}
