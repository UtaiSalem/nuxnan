<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHealthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'height' => 'nullable|numeric|between:0,300',
            'weight' => 'nullable|numeric|between:0,300',
            'allergies' => 'nullable|string|max:1000',
            'chronic_diseases' => 'nullable|string|max:1000',
            'medications' => 'nullable|string|max:1000',
            'blood_type' => 'nullable|string|max:10',
            'rh_factor' => 'nullable|string|max:10',
        ];
    }
}
