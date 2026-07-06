<?php

namespace App\Http\Requests\Academy\Behavior;

use Illuminate\Foundation\Http\FormRequest;

class ApproveBehaviorRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:1000', // สำหรับ rejection
        ];
    }
}
