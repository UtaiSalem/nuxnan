<?php
namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nickname' => 'nullable|string|max:30',
            'first_name_th' => 'nullable|string|max:50',
            'last_name_th' => 'nullable|string|max:50',
            'title_prefix_th' => 'nullable|string|max:10',
            'first_name_en' => 'nullable|string|max:50',
            'last_name_en' => 'nullable|string|max:50',
            'title_prefix_en' => 'nullable|string|max:10',
            'date_of_birth' => 'nullable|date',
            'nationality' => 'nullable|string|max:50',
            'religion' => 'nullable|string|max:50',
            'gender' => 'nullable|integer|in:0,1',
            'profile_image' => 'nullable|string|max:255',
        ];
    }
}
