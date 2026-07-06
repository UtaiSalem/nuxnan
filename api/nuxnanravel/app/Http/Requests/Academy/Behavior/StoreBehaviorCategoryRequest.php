<?php

namespace App\Http\Requests\Academy\Behavior;

use Illuminate\Foundation\Http\FormRequest;

class StoreBehaviorCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    } // ตรวจ permission ที่ controller

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:positive,negative',
            'default_points' => 'required|integer|min:1',
            'severity' => 'required|in:low,medium,high,critical',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }
}
