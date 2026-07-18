<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class StartDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['session_id' => ['required', 'uuid'], 'device_fingerprint' => ['nullable', 'string', 'max:200']];
    }
}
