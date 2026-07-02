<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHomeVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visit_date' => ['required', 'date'],
            'visit_time' => ['nullable', 'date_format:H:i'],
            'visitor_name' => ['required', 'string', 'max:255'],
            'visitor_position' => ['required', 'string', 'max:255'],
            'visit_status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'zone_id' => ['nullable', 'exists:home_visit_zones,id'],
            'observations' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:2000'],
            'follow_up' => ['nullable', 'string', 'max:2000'],
            'next_visit' => ['nullable', 'date', 'after:visit_date'],
        ];
    }
}
