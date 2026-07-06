<?php

namespace App\Http\Requests\Academy\Behavior;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBehaviorSessionRecordsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academy = $this->route('academy');

        return [
            'records' => 'required|array|min:1|max:100',
            'records.*.student_id' => ['required', 'integer', Rule::exists('students', 'id')->where('academy_id', $academy->id)],
            'records.*.category_id' => ['nullable', 'integer', Rule::exists('behavior_categories', 'id')->where('academy_id', $academy->id)],
            'records.*.type' => 'required|in:positive,negative',
            'records.*.points' => 'required|integer|min:1',
            'records.*.title' => 'required|string|max:255',
            'records.*.description' => 'nullable|string',
            'records.*.severity' => 'required|in:low,medium,high,critical',
        ];
    }
}
