<?php
namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guardian.guardian_type' => 'required|in:father,mother,guardian,relative',
            'guardian.citizen_id' => 'nullable|string|max:13',
            'guardian.title_prefix' => 'nullable|string|max:20',
            'guardian.first_name' => 'required|string|max:100',
            'guardian.last_name' => 'required|string|max:100',
            'guardian.occupation' => 'nullable|string|max:100',
            'guardian.workplace' => 'nullable|string|max:200',
            'guardian.monthly_income' => 'nullable|numeric|min:0',
            'guardian.relationship' => 'nullable|string|max:50',
            'guardian.is_primary_contact' => 'boolean',
            'guardian.is_emergency_contact' => 'boolean',
            'contact.contact_type' => 'required|in:phone,email,line,facebook,other',
            'contact.contact_value' => 'required|string|max:100',
            'contact.is_primary' => 'boolean'
        ];
    }
}
