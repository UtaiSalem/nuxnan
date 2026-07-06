<?php

namespace App\Http\Requests\Academy\Behavior;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBehaviorSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academy = $this->route('academy');

        return [
            'classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')->where('academy_id', $academy->id)],
            'title' => 'required|string|max:255',
            'date' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}
