<?php

namespace App\Http\Requests\Academy\Rollover;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CommitRolloverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('enrollment.commit', $this->route('academy'));
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'uuid'],
            'confirm_text' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'confirm_text' => trim((string) $this->input('confirm_text')),
        ]);
    }
}
