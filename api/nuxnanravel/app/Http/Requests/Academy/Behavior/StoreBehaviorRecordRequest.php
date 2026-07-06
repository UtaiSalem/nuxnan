<?php

namespace App\Http\Requests\Academy\Behavior;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBehaviorRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academy = $this->route('academy');

        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')->where('academy_id', $academy->id)],
            'category_id' => ['nullable', 'integer', Rule::exists('behavior_categories', 'id')->where('academy_id', $academy->id)],
            'type' => 'required|in:positive,negative',
            'points' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'incident_date' => 'nullable|date',
            'severity' => 'required|in:low,medium,high,critical',
            'evidence' => 'nullable|array',
        ];
    }
}
