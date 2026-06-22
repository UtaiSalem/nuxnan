<?php

namespace App\Http\Requests\Academy\Rollover;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UndoRolloverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('enrollment.undo', [
            $this->route('academy'),
            $this->route('batch'),
        ]);
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
