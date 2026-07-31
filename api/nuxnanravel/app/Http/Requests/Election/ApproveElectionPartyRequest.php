<?php

namespace App\Http\Requests\Election;

use Illuminate\Foundation\Http\FormRequest;

class ApproveElectionPartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['number' => 'nullable|integer|min:1'];
    }
}
