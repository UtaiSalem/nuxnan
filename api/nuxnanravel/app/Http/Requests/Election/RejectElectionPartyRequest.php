<?php

namespace App\Http\Requests\Election;

use Illuminate\Foundation\Http\FormRequest;

class RejectElectionPartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['review_note' => 'required|string'];
    }
}
