<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class RecordImpressionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['placement' => ['nullable', 'string', 'max:50'], 'idempotency_key' => ['nullable', 'string', 'max:100']];
    }
}
