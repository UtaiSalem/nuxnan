<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_type' => ['required', Rule::in(['current', 'permanent', 'temporary'])],
            'house_number' => 'required|string|max:50',
            'village_number' => 'nullable|string|max:20',
            'village_name' => 'nullable|string|max:100',
            'alley' => 'nullable|string|max:100',
            'road' => 'nullable|string|max:100',
            'subdistrict' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'is_current' => 'boolean',
        ];
    }
}
