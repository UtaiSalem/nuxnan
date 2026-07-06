<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_type' => ['required', Rule::in(['phone', 'email', 'line', 'facebook', 'other'])],
            'contact_value' => 'required|string|max:100',
            'is_primary' => 'boolean',
        ];
    }
}
