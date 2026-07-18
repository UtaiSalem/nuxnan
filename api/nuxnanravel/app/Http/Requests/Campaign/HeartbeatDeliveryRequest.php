<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class HeartbeatDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['visibility_ratio' => ['required', 'numeric', 'between:0,1'], 'token' => ['required', 'string', 'max:512']];
    }
}
